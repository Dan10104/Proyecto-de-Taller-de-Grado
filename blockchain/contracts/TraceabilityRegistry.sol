// SPDX-License-Identifier: MIT
pragma solidity 0.8.38;

import {Ownable} from "@openzeppelin/contracts/access/Ownable.sol";

/// @notice Complementary evidence only: no business objects or personal data.
contract TraceabilityRegistry is Ownable {
    mapping(bytes32 evidenceHash => uint256 timestamp) private registrations;
    error ZeroEvidenceHash();
    event EvidenceRegistered(bytes32 indexed evidenceHash, uint256 timestamp);

    constructor(address writer) Ownable(writer) {}

    function registerEvidence(bytes32 evidenceHash) external onlyOwner returns (uint256) {
        if (evidenceHash == bytes32(0)) revert ZeroEvidenceHash();
        uint256 timestamp = registrations[evidenceHash];
        if (timestamp == 0) {
            timestamp = block.timestamp;
            registrations[evidenceHash] = timestamp;
            emit EvidenceRegistered(evidenceHash, timestamp);
        }
        return timestamp;
    }

    function isRegistered(bytes32 evidenceHash) external view returns (bool) {
        return registrations[evidenceHash] != 0;
    }

    function registeredAt(bytes32 evidenceHash) external view returns (uint256) {
        return registrations[evidenceHash];
    }
}
