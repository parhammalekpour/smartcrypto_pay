// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

import "@openzeppelin/contracts/token/ERC20/ERC20.sol";

/**
 * @title SmartCryptoPay Test USDT (testnet)
 * @notice This is a test ERC-20 token used only on testnets. NOT real Tether (USDT).
 *
 * Token details:
 *  - Name: SmartCryptoPay Test USDT
 *  - Symbol: USDT
 *  - Decimals: 6
 *  - Initial supply: provided at deployment (minted to deployer)
 */
contract TestUSDT is ERC20 {
    uint8 private constant _decimals = 6;

    constructor(uint256 initialSupply) ERC20("SmartCryptoPay Test USDT", "USDT") {
        _mint(msg.sender, initialSupply);
    }

    function decimals() public pure override returns (uint8) {
        return _decimals;
    }
}
