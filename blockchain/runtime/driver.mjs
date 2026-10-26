import { Contract, Wallet, JsonRpcProvider, FetchRequest, keccak256, parseUnits } from 'ethers';
import { readFile } from 'node:fs/promises';

export const SEPOLIA = 11155111;
const referencePattern = /^eip155:11155111\/(0x[0-9a-f]{40})\/(0x[0-9a-f]{64})$/;
const fail = () => { throw new Error('UNAVAILABLE'); };

export async function connect(environment = process.env) {
  if (Number(environment.BLOCKCHAIN_CHAIN_ID) !== SEPOLIA) fail();
  const url = new URL(environment.BLOCKCHAIN_RPC_URL);
  if (url.protocol !== 'https:' && !(url.protocol === 'http:' && ['127.0.0.1', 'localhost', '[::1]'].includes(url.hostname))) fail();
  const request = new FetchRequest(url.href);
  request.timeout = 8000;
  const provider = new JsonRpcProvider(request, SEPOLIA, { staticNetwork: true, batchMaxCount: 1 });
  try {
    if (Number(await provider.send('eth_chainId', [])) !== SEPOLIA) fail();
    return provider;
  } catch { provider.destroy(); fail(); }
}

export async function execute(input, environment = process.env, candidate = () => {}) {
  if (!['chain', 'exists', 'timestamp', 'register', 'find'].includes(input.action)) fail();
  const provider = await connect(environment);
  try {
    if (input.action === 'chain') return { chainId: SEPOLIA };
    if (!/^[0-9a-f]{64}$/.test(input.hash ?? '') || /^0+$/.test(input.hash)) fail();
    const hash = '0x' + input.hash;
    const address = (environment.BLOCKCHAIN_CONTRACT_ADDRESS ?? '').toLowerCase();
    if (!/^0x[0-9a-f]{40}$/.test(address) || /^0x0+$/.test(address)) fail();
    const metadata = JSON.parse(await readFile(new URL('../abi/TraceabilityRegistry.json', import.meta.url), 'utf8'));
    const code = await provider.getCode(address);
    if (code === '0x' || keccak256(code) !== metadata.runtimeCodeHash) fail();
    const contract = new Contract(address, metadata.abi, provider);
    const timestamp = Number(await contract.registeredAt(hash));
    if (input.action === 'exists') return { registered: timestamp > 0 };
    if (input.action === 'timestamp') return { timestamp: timestamp || null };
    const reference = (tx) => `eip155:11155111/${address}/${tx.toLowerCase()}`;
    const confirmations = Math.max(1, Number(environment.BLOCKCHAIN_CONFIRMATIONS ?? 1));
    if (!Number.isSafeInteger(confirmations) || confirmations > 100) fail();

    async function prove(txHash) {
      const receipt = await provider.getTransactionReceipt(txHash);
      if (!receipt) return { status: 'PENDING', reference: reference(txHash) };
      const [tx, block, latest] = await Promise.all([provider.getTransaction(txHash), provider.getBlock(receipt.blockNumber), provider.getBlockNumber()]);
      if (!block || block.hash !== receipt.blockHash) return { status: 'PENDING', reference: reference(txHash) };
      let call;
      try { call = contract.interface.parseTransaction({ data: tx?.data, value: tx?.value }); } catch { /* mismatched call */ }
      if (!tx || tx.chainId !== BigInt(SEPOLIA) || tx.to?.toLowerCase() !== address || receipt.to?.toLowerCase() !== address || tx.value !== 0n || call?.name !== 'registerEvidence' || call.args[0].toLowerCase() !== hash) {
        return { status: 'INCONSISTENT', reference: reference(txHash) };
      }
      if (latest - receipt.blockNumber + 1 < confirmations) return { status: 'PENDING', reference: reference(txHash) };
      if (receipt.status !== 1) return { status: 'FAILED', reference: reference(txHash) };
      const events = receipt.logs.filter(log => log.address.toLowerCase() === address).flatMap(log => {
        try { const parsed = contract.interface.parseLog(log); return parsed?.name === 'EvidenceRegistered' ? [parsed] : []; } catch { return []; }
      });
      if (events.length !== 1 || events[0].args[0].toLowerCase() !== hash || Number(events[0].args[1]) !== block.timestamp || timestamp !== block.timestamp) {
        return { status: 'INCONSISTENT', reference: reference(txHash) };
      }
      if (Number(await contract.registeredAt(hash, {blockTag:receipt.blockNumber})) !== block.timestamp) return {status:'INCONSISTENT',reference:reference(txHash)};
      return { status: 'CONFIRMED', reference: reference(txHash), timestamp: block.timestamp };
    }

    async function recover() {
      if (!timestamp) return { status: 'ABSENT' };
      let first = Number(environment.BLOCKCHAIN_DEPLOYMENT_BLOCK ?? 0);
      const last = await provider.getBlockNumber();
      if (!Number.isSafeInteger(first) || first < 0 || first > last) return { status: 'PENDING' };
      // registeredAt supplies a trusted historical timestamp. Narrow old deployments by
      // monotonic block timestamps, then retrieve indexed logs in bounded pages.
      let upper = last;
      while (upper - first > 1999) {
        const middle = Math.floor((first + upper) / 2);
        const block = await provider.getBlock(middle);
        if (!block) fail();
        if (block.timestamp < timestamp) first = middle + 1;
        else upper = middle;
      }
      const topics = contract.interface.encodeFilterTopics('EvidenceRegistered', [hash]);
      for (let start = first; start <= upper; start += 2000) {
        const logs = await provider.getLogs({ address, topics, fromBlock: start, toBlock: Math.min(start + 1999, upper) });
        if (logs.length > 1) return { status: 'INCONSISTENT' };
        if (logs.length === 1) return prove(logs[0].transactionHash);
      }
      return { status: 'PENDING' };
    }

    if (input.reference != null) {
      const parsed = referencePattern.exec(input.reference);
      if (!parsed || parsed[1] !== address) return { status: 'INCONSISTENT' };
      return await prove(parsed[2]);
    }
    if (input.action === 'find' || timestamp) return await recover();
    const wallet = new Wallet(environment.BLOCKCHAIN_PRIVATE_KEY, provider);
    if ((await contract.owner()).toLowerCase() !== wallet.address.toLowerCase()) fail();
    const writer = contract.connect(wallet);
    // The caller holds a cross-process signer mutex across this whole broadcast.
    const [gas, fees, nonce, balance] = await Promise.all([
      writer.registerEvidence.estimateGas(hash), provider.getFeeData(), provider.getTransactionCount(wallet.address, 'pending'), provider.getBalance(wallet.address),
    ]);
    if (!fees.maxFeePerGas || fees.maxPriorityFeePerGas == null || fees.maxFeePerGas > parseUnits('100', 'gwei')) fail();
    const gasLimit = gas * 120n / 100n;
    const cost = gasLimit * fees.maxFeePerGas;
    if (gasLimit > 500000n || cost > parseUnits('0.01', 'ether') || balance < cost) fail();
    const signed = await wallet.signTransaction({ chainId: SEPOLIA, to: address, data: contract.interface.encodeFunctionData('registerEvidence', [hash]), value: 0n, type: 2, nonce, gasLimit, maxFeePerGas: fees.maxFeePerGas, maxPriorityFeePerGas: fees.maxPriorityFeePerGas });
    const pending = { status: 'PENDING', reference: reference(keccak256(signed)) };
    // Export only the public candidate before broadcast. A killed process still exposes its tx hash.
    candidate(pending);
    try { await provider.broadcastTransaction(signed); } catch { /* uncertain: preserve candidate; never claim failure/confirmation */ }
    return pending;
  } finally { provider.destroy(); }
}
