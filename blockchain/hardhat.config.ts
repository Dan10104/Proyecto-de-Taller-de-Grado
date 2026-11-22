import { defineConfig } from 'hardhat/config';
import ethers from '@nomicfoundation/hardhat-ethers';
import nodeTest from '@nomicfoundation/hardhat-node-test-runner';
import { createRequire } from 'node:module';
const require = createRequire(import.meta.url);

export default defineConfig({
  plugins: [ethers, nodeTest],
  solidity: {
    version: '0.8.38',
    path: require.resolve('solc/soljson.js'),
    settings: { optimizer: { enabled: true, runs: 200 }, evmVersion: 'cancun' },
  },
  networks: {
    hardhatMainnet: { type: 'edr-simulated', chainType: 'l1', chainId: 11155111 },
    // Local network compatible with Sepolia (scripts/sepolia-local.mjs). The genesis date is fixed so that the
    // journal of past transactions can be replayed on its original block timestamps after a restart. As on a
    // public network, a transaction whose execution reverts is still mined and answered with its hash.
    sepoliaLocal: { type: 'edr-simulated', chainType: 'l1', chainId: 11155111, initialDate: '2026-01-01T00:00:00Z', throwOnTransactionFailures: false },
  },
});
