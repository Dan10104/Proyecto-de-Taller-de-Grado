import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { keccak256 } from 'ethers';
const artifact = JSON.parse(await readFile(new URL('../artifacts/contracts/TraceabilityRegistry.sol/TraceabilityRegistry.json', import.meta.url), 'utf8'));
await mkdir(new URL('../abi/', import.meta.url), { recursive: true });
await writeFile(new URL('../abi/TraceabilityRegistry.json', import.meta.url), JSON.stringify({ contractName: artifact.contractName, compiler: '0.8.38', optimizerRuns: 200, evmVersion: 'cancun', runtimeCodeHash: keccak256(artifact.deployedBytecode), abi: artifact.abi }, null, 2) + '\n');
console.log('ABI y huella de bytecode exportados.');
