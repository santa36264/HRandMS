<template>
  <div class="legal-page">
    <div class="legal-hero">
      <h1>Privacy Policy</h1>
      <p>Last updated: January 1, 2026</p>
    </div>

    <div class="legal-body">

      <section>
        <h2>1. Introduction</h2>
        <p>SATAAB Hotel ("we", "our", "us") is committed to protecting your personal information. This Privacy Policy explains what data we collect, how we use it, and your rights regarding that data when you use our hotel reservation platform.</p>
      </section>

      <section>
        <h2>2. Information We Collect</h2>
        <ul>
          <li><strong>Account information:</strong> Name, email address, phone number, and password (stored encrypted).</li>
          <li><strong>Booking information:</strong> Room preferences, check-in/check-out dates, number of guests, and special requests.</li>
          <li><strong>Payment information:</strong> Transaction reference numbers and payment status. We do not store card numbers or mobile banking credentials — these are handled entirely by Chapa.</li>
          <li><strong>Usage data:</strong> Pages visited, browser type, IP address, and device information for security and analytics purposes.</li>
          <li><strong>Communications:</strong> Messages you send us via email or support channels.</li>
        </ul>
      </section>

      <section>
        <h2>3. How We Use Your Information</h2>
        <ul>
          <li>To process and manage your hotel reservations.</li>
          <li>To send booking confirmations, payment receipts, check-in reminders, and review requests via email.</li>
          <li>To verify your identity and prevent fraud.</li>
          <li>To improve our platform and personalize your experience.</li>
          <li>To comply with legal obligations under Ethiopian law.</li>
        </ul>
      </section>

      <section>
        <h2>4. Email Communications</h2>
        <p>By creating an account, you consent to receiving transactional emails including booking confirmations, payment receipts, check-in reminders, and review requests. These are essential to the service and cannot be opted out of while your account is active. You may unsubscribe from promotional emails at any time via the link in the email footer.</p>
      </section>

      <section>
        <h2>5. Data Sharing</h2>
        <p>We do not sell your personal data. We may share your information with:</p>
        <ul>
          <li><strong>Chapa</strong> — our payment processor, solely for the purpose of completing transactions.</li>
          <li><strong>Email service providers</strong> — to deliver transactional emails on our behalf.</li>
          <li><strong>Law enforcement</strong> — when required by Ethiopian law or court order.</li>
        </ul>
      </section>

      <section>
        <h2>6. Data Security</h2>
        <p>We implement industry-standard security measures including encrypted passwords (bcrypt), HTTPS-only communication, bearer token authentication (Laravel Sanctum), and access controls. However, no system is 100% secure and we cannot guarantee absolute security.</p>
      </section>

      <section>
        <h2>7. Data Retention</h2>
        <p>We retain your account and booking data for as long as your account is active or as required by Ethiopian tax and hospitality regulations (typically 7 years for financial records). You may request deletion of your account and personal data by contacting us directly.</p>
      </section>

      <section>
        <h2>8. Your Rights</h2>
        <p>You have the right to:</p>
        <ul>
          <li>Access the personal data we hold about you.</li>
          <li>Request correction of inaccurate data.</li>
          <li>Request deletion of your account and associated data.</li>
          <li>Object to processing of your data for marketing purposes.</li>
        </ul>
        <p style="margin-top:10px">To exercise any of these rights, contact us at <a href="mailto:semredemssie83@gmail.com">semredemssie83@gmail.com</a>.</p>
      </section>

      <section>
        <h2>9. Cookies</h2>
        <p>We use minimal cookies for session management and security. We do not use third-party advertising cookies. You can disable cookies in your browser settings, though this may affect functionality.</p>
      </section>

      <section>
        <h2>10. Children's Privacy</h2>
        <p>Our Service is not directed at children under 18. We do not knowingly collect personal data from minors. If you believe a minor has provided us with personal data, please contact us immediately.</p>
      </section>

      <section>
        <h2>11. Changes to This Policy</h2>
        <p>We may update this Privacy Policy periodically. We will notify you of significant changes via email or a prominent notice on our platform. Continued use of the Service after changes constitutes acceptance.</p>
      </section>

      <section>
        <h2>12. Contact Us</h2>
        <p>For any privacy-related questions or requests, please contact:</p>
        <ul>
          <li>📧 <a href="mailto:semredemssie83@gmail.com">semredemssie83@gmail.com</a></li>
          <li>📍 Addis Ababa, Ethiopia</li>
          <li>📞 +251 911 000 000</li>
        </ul>
      </section>

    </div>
  </div>
</template>

<script setup>
// Static page — no logic needed
</script>

<style scoped>
.legal-page {
  min-height: 60vh;
  padding-bottom: 60px;
}

.legal-hero {
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  color: #fff;
  text-align: center;
  padding: 64px 24px 48px;
}
.legal-hero h1 {
  font-size: clamp(1.8rem, 4vw, 2.6rem);
  font-weight: 900;
  margin-bottom: 10px;
  color: #c9a84c;
}
.legal-hero p {
  font-size: 14px;
  color: #94a3b8;
}

.legal-body {
  max-width: 820px;
  margin: 0 auto;
  padding: 48px 24px;
  display: flex;
  flex-direction: column;
  gap: 36px;
}

section h2 {
  font-size: 1.1rem;
  font-weight: 800;
  color: #1a1a2e;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 2px solid #c9a84c;
  display: inline-block;
}

section p, section li {
  font-size: 15px;
  color: #374151;
  line-height: 1.8;
}

section ul {
  padding-left: 20px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

section ul li::marker {
  color: #c9a84c;
}

section a {
  color: #c9a84c;
  text-decoration: underline;
}

strong {
  color: #1a1a2e;
}
</style>
