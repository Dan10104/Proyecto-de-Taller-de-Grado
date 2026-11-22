import { Contract, Wallet, JsonRpcProvider, FetchRequest, keccak256, parseUnits } from 'ethers';
import { readFile } from 'node:fs/promises';

export const SEPOLIA = 11155111;
const referencePattern = /^eip155:11155111\/(0x[0-9a-f]{40})\/(0x[0-9a-f]{64})$/;
const fail = () => { throw new Error('UNAVAILABLE'); };

// Both environments use Sepolia's chain id; they are never interchangeable. «public» is the real testnet,
// reached over https. «local-simulation» is the project's local network (scripts/sepolia-local.mjs), reached
// only on loopback, and it must identify itself as such.
export const ENVIRONMENTS = ['public', 'local-simulation'];
const LOCAL_CLIENT = /^EspacioU-SepoliaLocal\//;
// Hash of the genesis block of the public Sepolia testnet: the positive identity of the public network.
const SEPOLIA_GENESIS = '0x25a5cc106eea7138acab33231d7160d69cb777ee0c2c553fcddf5138993e6dd9';
// Address of Hardhat's first development account, the writer of the local simulation. Its key is public
// knowledge, so it never signs on the public testnet. As a consequence, a contract address of the simulation
// can never be the address of a public deployment of this project.
export const DEVELOPMENT_SIGNER = '0xf39fd6e51aad88f6f4ce6ab8827279cfffb92266';
export function environmentOf(environment = process.env) {
  const value = environment.BLOCKCHAIN_ENVIRONMENT ?? 'public';
  if (!ENVIRONMENTS.includes(value)) fail();
  return value;
}
export function signer(environment, provider) {
  const wallet = new Wallet(environment.BLOCKCHAIN_PRIVATE_KEY, provider);
  if (environmentOf(environment) === 'public' && wallet.address.toLowerCase() === DEVELOPMENT_SIGNER) fail();
  return wallet;
}

export async function connect(environment = process.env) {
  if (Number(environment.BLOCKCHAIN_CHAIN_ID) !== SEPOLIA) fail();
  const simulated = environmentOf(environment) === 'local-simulation';
  const url = new URL(environment.BLOCKCHAIN_RPC_URL);
  const loopback = url.protocol === 'http:' && ['127.0.0.1', 'localhost', '[::1]'].includes(url.hostname);
  if (simulated ? !loopback : url.protocol !== 'https:') fail();
  const request = new FetchRequest(url.href);
  request.timeout = 8000;
  // The endpoint that was validated is the only one that is ever contacted: a redirect is a failure.
  const fetchUrl = FetchRequest.createGetUrlFunc();
  request.getUrlFunc = async (req, signal) => {
    const response = await fetchUrl(req, signal);
    if (response.statusCode >= 300 && response.statusCode < 400) throw new Error('REDIRECT');
    return response;
  };
  const provider = new JsonRpcProvider(request, SEPOLIA, { staticNetwork: true, batchMaxCount: 1 });
  try {
    if (Number(await provider.send('eth_chainId', [])) !== SEPOLIA) fail();
    // Both networks share the chain id, so each one must prove which it is. Public Sepolia is recognised by
    // its genesis block; the local network by its own name, and it never carries the public genesis.
    // Anything that cannot be identified is refused.
    const genesis = await provider.send('eth_getBlockByNumber', ['0x0', false]);
    if (typeof genesis?.hash !== 'string' || !/^0x[0-9a-f]{64}$/i.test(genesis.hash)) fail();
    const publicChain = genesis.hash.toLowerCase() === SEPOLIA_GENESIS;
    let client = '';
    try { client = String(await provider.send('web3_clientVersion', [])); } catch { /* not implemented upstream */ }
    const localNode = LOCAL_CLIENT.test(client);
    if (simulated ? (!localNode || publicChain) : (localNode || !publicChain)) fail();
    return provider;
  } catch { provider.destroy(); fail(); }
}

export async function execute(input, environment = process.env, candidate = () => {}) {
  if (!['chain', 'exists', 'timestamp', 'register', 'find'].includes(input.action)) fail();
  const provider = await connect(environment);
  try {
    if (input.action === 'chain') return { chainId: SEPOLIA, environment: environmentOf(environment) };
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
      // Public facts of the confirmation, for display only: nothing here is trusted as proof by the caller.
      return { status: 'CONFIRMED', reference: reference(txHash), timestamp: block.timestamp, block: receipt.blockNumber, confirmations: latest - receipt.blockNumber + 1 };
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
    const wallet = signer(environment, provider);
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
