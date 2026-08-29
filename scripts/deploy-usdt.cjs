/*
 * Deployment script for SmartCryptoPay Test USDT (testnet)
 *
 * Usage (after setting env vars):
 *   - Ensure ALCHEMY_API_KEY is set (project already expects this)
 *   - Set DEPLOYER_PRIVATE_KEY to the deployer account private key (0x...)
 *   - Run: npx hardhat run --network sepolia scripts/deploy-usdt.js
 *
 * This script does NOT run automatically; it only provides the deployment steps.
 */

const hre = require("hardhat");

async function main() {
  const networkName = hre.network.name;
  console.log(`Network: ${networkName}`);

  const accounts = await hre.ethers.getSigners();
  const deployer = accounts[0];
  console.log("Deploying contracts with the account:", deployer.address);

  // Initial supply: 1,000,000 USDT with 6 decimals
  const initialSupply = hre.ethers.utils.parseUnits("1000000", 6);

  const TestUSDT = await hre.ethers.getContractFactory("TestUSDT");
  const usdt = await TestUSDT.deploy(initialSupply);
  await usdt.deployed();

  console.log("TestUSDT deployed to:", usdt.address);
  console.log("Token name:", await usdt.name());
  console.log("Token symbol:", await usdt.symbol());
  console.log("Token decimals:", (await usdt.decimals()).toString());
  console.log("Initial supply (raw):", initialSupply.toString());
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
