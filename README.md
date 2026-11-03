# WooCommerce TourCMS Connector

A custom WordPress plugin that integrates WooCommerce with the TourCMS booking API, connecting online purchases with tour reservations and digital vouchers.

## Features

- Connects WooCommerce booking workflows with the TourCMS API.
- Checks tour availability before creating bookings.
- Submits booking data to TourCMS.
- Retrieves booking details and voucher information.
- Supports QR-code voucher retrieval for digital products such as Vienna PASS.
- Provides an administration interface for API connection tests and booking-flow simulations.
- Loads API configuration through environment variables.

## Technical Details

- **Platform:** WordPress
- **E-commerce:** WooCommerce
- **Language:** PHP
- **External API:** TourCMS REST API
- **Data Format:** XML / SimpleXML
- **Integration:** Booking and voucher workflows

## Installation

1. Copy the plugin directory into `wp-content/plugins/`.
2. Configure the required TourCMS credentials and IDs in the environment configuration.
3. Activate **Tamino TourCMS Connector** in the WordPress admin dashboard.
4. Use the TourCMS Connector administration page to test the API connection.

## Project Context

Developed for Tamino Travel to connect WooCommerce-based digital tour products with TourCMS booking and voucher services.

## Author

**Willie Springer**

- Website: https://www.ki-o.eu
- GitHub: https://github.com/tha-springer-dude
