# Simple Members Access Plugin

A lightweight and flexible WordPress plugin to manage a gated members-only area with frontend login/registration, admin approval, role-based page protection, and dynamic settings.

---

## 🎯 Features

- Frontend login and registration forms (shortcodes)
- Members must be **approved** by an admin before accessing restricted pages
- Two custom roles:
  - `pending_member` — default role on registration
  - `approved_member` — can access protected content
- Admin approval triggers **custom email notification** to user
- Protected pages via “Members Only” checkbox
- Settings panel to configure redirects, email behavior, and page destinations
- All forms styled with scoped, mobile-friendly utility classes

---

## 🧩 Shortcodes

Place these shortcodes on pages to render the login/registration forms:

**Login Form**
```
[members_login]
```

**Registration Form**
```
[members_register]
```

---

## ⚙️ Settings Page

Go to **Settings → Members Access** to configure plugin options.

### 🔹 General

- **Members Registration Page**: Select the page where the `[members_register]` shortcode is located. Used in login form link.
- **Members Login Page**: Select the page where the `[members_login]` shortcode is located. Used in registration form link and email links.

### 🔹 Redirects

- **Redirect Logged-In Users**: If enabled, users who are already logged in will be redirected away from the login form.
- **Redirect Destination for Logged-In Users**: Select the page where logged-in users will be redirected to (e.g. `/members-dashboard`).

### 🔹 Email

- **Include Login Link in Approval Email**: If checked, the approval email will include a login link.
- **Login Page for Email Link**: Select the login page to be included in the approval email.

---

## 🔐 Protecting Pages

On any WordPress page, check the box labeled **“Members Only”** to restrict that page to logged-in, approved members only.

Only users with the `approved_member` role or other elevated roles (like admin/editor) can view restricted pages.  
`pending_member` users and logged-out visitors will be redirected to the login page.

---

## ✉️ Email Notifications

- When a user registers, the site admin is emailed.
- When a user is manually updated from `pending_member` to `approved_member`, the user receives an approval email.
- If enabled, the email includes a link to the login page selected in settings.

---

## ✅ Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate via the **Plugins** menu
3. Configure via **Settings → Members Access**
4. Create pages for login and registration, and insert the shortcodes
5. You're good to go!

---

## 📌 Notes

- This plugin is designed to be theme-agnostic and uses its own scoped CSS (`members-ui-*`) for styling
- You can extend this plugin to support custom dashboard pages, file tracking, or WooCommerce integration

---

## 🧑‍💻 Support

Need help or want to customise the plugin?  
Contact **AY Studio** at [digital@ay.studio](mailto:digital@ay.studio)
