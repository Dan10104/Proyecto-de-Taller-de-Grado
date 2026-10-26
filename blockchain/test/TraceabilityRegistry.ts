import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { network } from 'hardhat';

describe('TraceabilityRegistry', async () => {
  const { ethers } = await network.create('hardhatMainnet');
  it('records only a hash, protects writer, rejects zero, preserves first timestamp', async () => {
    const [owner, stranger] = await ethers.getSigners();
    const registry = await ethers.deployContract('TraceabilityRegistry', [owner.address]);
    const hash = ethers.sha256(ethers.toUtf8Bytes('synthetic-evidence'));
    assert.equal(await registry.isRegistered(hash), false);
    await assert.rejects(registry.connect(stranger).registerEvidence(hash));
    await assert.rejects(registry.registerEvidence(ethers.ZeroHash));
    const receipt = await (await registry.registerEvidence(hash)).wait();
    const timestamp = await registry.registeredAt(hash);
    assert.equal(await registry.isRegistered(hash), true);
    assert.equal(receipt.logs.length, 1);
    assert.equal(timestamp, BigInt((await ethers.provider.getBlock(receipt.blockNumber))!.timestamp));
    const duplicate = await (await registry.registerEvidence(hash)).wait();
    assert.equal(duplicate.logs.length, 0);
    assert.equal(await registry.registeredAt(hash), timestamp);
    await assert.rejects(owner.sendTransaction({ to: await registry.getAddress(), value: 1n }));
  });
});
