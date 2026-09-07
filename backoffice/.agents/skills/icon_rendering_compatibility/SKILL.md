---
name: Icon Rendering Compatibility (Material Symbols & Remix Icons)
description: Guidelines on handling rating star icons, Material Symbols Outlined FILL axis issues, and fallback using Remix Icons.
---

# Icon Rendering Compatibility: Material Symbols vs Remix Icons

This skill guides you on how to handle star rating icon rendering issues in this project, particularly when dealing with Google Material Symbols Outlined and Remix Icons.

## The Problem
Google **Material Symbols Outlined** (loaded via `google-icon.css`) relies on variable font features to toggle between outline and filled states:
*   Filled icon: `font-variation-settings: 'FILL' 1;`
*   Outline icon: `font-variation-settings: 'FILL' 0;` (default)
*   **Crucial detail**: In Material Symbols, both filled and outline stars are named `star`. The icon name `star_outline` does **not** exist in the Outlined variant.

If the project loads a static font file rather than the variable font API, or if browser compatibility prevents variable font features, `font-variation-settings` is ignored. This causes:
*   Either all stars rendering as outlined (default glyph).
*   Or all stars rendering as filled (due to fallback matching of the `star` prefix).

## The Solution
When variable font axis manipulation fails or is risky, use **Remix Icons** (which is loaded globally in `header.php` via `remixicon.css`). Remix Icons uses traditional distinct class names for outline and filled states, ensuring 100% compatibility:
*   **Filled Star**: `<i class="ri-star-fill" style="font-size:16px;"></i>`
*   **Outline Star**: `<i class="ri-star-line" style="font-size:16px;"></i>`

### CSS Styling Rule
Always ensure CSS rules targeting star colors cover both Material Symbols and Remix Icons:
```

## Troubleshooting IP Whitelist & API Authentication (ThaiBulkSMS)

### 1. IP Ban/Whitelist Issues
If the API returns connection timeouts, connection refused, or blocking errors (e.g., 403 Forbidden or specific IP block messages), the server's public IP needs to be whitelisted:
* Use `check_ip.php` (`https://icanhazip.com`) to find the server's current public IP.
* Whitelist the IP in the ThaiBulkSMS Developer Console or respective third-party provider settings.

### 2. Authentication Failed (Error 100)
If the API returns `{ "error": { "code": 100, "name": "ERROR_AUTHENTICATION_FAILED", "description": "Authentication failed." } }`:
* **Cause**: The credentials (`THAIBULK_KEY` and `THAIBULK_SECRET` or `THAIBULK_BASE64`) configured in the `.env` file are incorrect, inactive, or expired.
* **Resolution**:
  1. Log in to the [ThaiBulkSMS Console](https://member.thaibulksms.com/).
  2. Navigate to **API Settings** > **API Key**.
  3. Verify if the Key and Secret match the ones in `.env`. If not, copy the correct credentials or generate a new key pair and update `.env`.

