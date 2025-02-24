# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.3.1] - 2025-02-24
### Changed
- PLGMAG2V2-829: Enable Sofort and Dotpay payment methods

### Fixed
- Fix misplaced closing bracket in payment component template

## [2.3.0] - 2025-01-09
### Added
- DAVAMS-817: Added the Bizum payment method
- DAVAMS-852: Add the Billink payment method

### Fixed
- MAGWIRE-26: Fixed an issue where the Card payment icon was not changing based on selected configuration setting

### Removed
- PLGMAG2V2-810: Removed deprecated methods: Santander, Giropay, Sofort, Request to pay and Dotpay

## [2.2.1] - 2024-10-30
### Fixed
- MAGWIRE-22: Fixed a bug where Apple Pay would be visible on unsupported devices

## [2.2.0] - 2024-08-30
### Added
- DAVAMS-733: Added the BNPL_MF payment method
- MAGWIRE-18: Added the VVVBON payment method
- Added Maestro method and icon (thanks to @alphaLeporis)
- PLGMAG2V2-776: Added payment component to (BNPL) supported methods
- PLGMAG2V2-789: Added payment component for separate card gateways (Visa, Mastercard, American Express and Maestro)

### Changed
- The Quote is now stored in a class variable on retrieval to improve performance (thanks to @pykettk)

### Removed
- PLGMAG2V2-783: Removed issuers according to the iDEAL 2.0 standard

## [2.1.0] - 2024-02-16
### Added
- DAVAMS-716: Add Multibanco payment method
- DAVAMS-724: Add MB WAY payment method

## [2.0.0] - 2024-01-25
### Added
- PLGMAG2V2-709: Added payment component for Card Payment
- Added escapers and made strings translateable (thanks to @jesse-deboer)
- Added support for preselected payment method (thanks to @jesse-deboer)
- PLGMAG2V2-717: Added payment icons to all the gateways and gift cards (thanks to @xgerhard)

### Fixed
- Fixed issue 'CRITICAL: Magewire: Warning: Undefined array key issuer_id' happening in some cases (thanks to @jesse-deboer)

### Changed
- Changed from using container to ReferenceContainer for the payment methods in the Hyva xml layout (thanks to @jesse-deboer)
- Removed the use of PlaceOrder and replaced it with canPlaceOrder (thanks to @jesse-deboer)
- Changed to use checkout.payment.methods ReferenceBlock (thanks to @xgerhard)

### Removed
- DAVAMS-708: Removed Santander Betaal per Maand

## [1.0.0] - 2023-04-13

### Added
- Added compatibility with Magewire Checkout version 1.0.0

### Changed
- Upgraded composer dependencies
- Removed year from copyright notice

### Removed
- Removed legacy files

## [1.0.0-beta] - 2022-11-21
- First public beta release
