### Project Introduction
**SAFAR** is a dynamic, full-stack travel marketplace designed to seamlessly connect travel agencies with globetrotters. Built on a robust PHP and MySQL architecture, the platform serves as a centralized hub where verified travel agencies can curate and list premium tour packages and hotel accommodations. For travelers, SAFAR offers an intuitive, visually engaging interface to discover, compare, and reserve their next adventure. With an emphasis on user experience, secure role-based access, and streamlined booking workflows, SAFAR modernizes the travel reservation process for both vendors and customers.

### Project Summary
The application functions as a multi-tenant booking ecosystem utilizing a three-tiered user architecture: Travelers, Agencies, and Administrators. The backend relies on vanilla PHP integrated with PDO for secure, prepared database transactions, while the frontend leverages responsive HTML, custom CSS with modern glassmorphism aesthetics, and JavaScript for asynchronous search filtering. 

When a travel agency registers, they undergo a mandatory verification process governed by the system administrators. Once approved, agencies gain access to a dedicated dashboard to manage their listings and process incoming booking requests. Travelers can seamlessly browse global destinations, view comprehensive package details, and execute simulated reservations via an interactive demo-payment gateway. The entire ecosystem is overseen by an Admin panel that provides granular control over user management, agency approvals, and global system analytics.

### Key Points & Features
* **Role-Based Access Control (RBAC):** Distinct, secure environments and workflows for three user types:
  * **Travelers:** Can browse packages, submit booking requests, and track trip histories.
  * **Agencies:** Have dedicated dashboards to create/manage listings and approve/reject bookings.
  * **Administrators:** Possess global oversight to manage users, moderate packages, and verify agency credentials.
* **Agency Verification Workflow:** Built-in quality control ensures that newly registered travel agencies cannot publish packages until their account is manually reviewed and verified by an Administrator.
* **Dynamic Search & Filtering:** An asynchronous exploration interface allows users to seamlessly filter listings by type (tours vs. hotels), location, and price parameters without page reloads.
* **Interactive Booking Experience:** Features a modern, sticky booking widget and a responsive demo-payment modal that simulates the checkout process before logging the transaction in the database.
* **Robust Data Security:** Implements industry best practices, including `password_hash()` for cryptographic credential storage and PDO prepared statements to mitigate SQL injection vulnerabilities across all database operations.
* **Profile Management:** All users have access to self-service profile management, allowing them to update personal information, securely change passwords, and upload custom profile avatars.
