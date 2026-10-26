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
  networks: { hardhatMainnet: { type: 'edr-simulated', chainType: 'l1', chainId: 11155111 } },
});
