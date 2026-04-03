<template>
  <div class="legal-page">
    <div class="legal-hero">
      <h1>Terms of Service</h1>
      <p>Last updated: January 1, 2026</p>
    </div>

    <div class="legal-body">

      <section>
        <h2>1. Acceptance of Terms</h2>
        <p>By accessing or using the SATAAB Hotel Hotel Reservation &amp; Management System ("Service"), you agree to be bound by these Terms of Service. If you do not agree, please do not use the Service.</p>
      </section>

      <section>
        <h2>2. Eligibility</h2>
        <p>You must be at least 18 years old and legally capable of entering into binding contracts to use this Service. By registering, you confirm that all information you provide is accurate and complete.</p>
      </section>

      <section>
        <h2>3. Reservations &amp; Bookings</h2>
        <ul>
          <li>All bookings are subject to room availability at the time of confirmation.</li>
          <li>A booking is only confirmed once full payment has been successfully processed through our payment gateway (Chapa).</li>
          <li>You will receive a booking confirmation email with your unique booking reference number.</li>
          <li>SATAAB Hotel reserves the right to cancel any booking in the event of pricing errors, fraud, or force majeure, with a full refund issued to the guest.</li>
        </ul>
      </section>

      <section>
        <h2>4. Payments</h2>
        <ul>
          <li>All prices are displayed and charged in Ethiopian Birr (ETB).</li>
          <li>Payments are processed securely via Chapa. SATAAB Hotel does not store your card or mobile banking credentials.</li>
          <li>A payment receipt will be emailed to you upon successful transaction.</li>
          <li>In the event of a failed payment, no charge will be applied to your account.</li>
        </ul>
      </section>

      <section>
        <h2>5. Cancellation Policy</h2>
        <ul>
          <li>Cancellations made more than 48 hours before check-in are eligible for a full refund.</li>
          <li>Cancellations made within 48 hours of check-in may be subject to a one-night cancellation fee.</li>
          <li>No-shows will be charged the full booking amount.</li>
          <li>Refunds, where applicable, are processed within 5–10 business days via the original payment method.</li>
        </ul>
      </section>

      <section>
        <h2>6. Check-In &amp; Check-Out</h2>
        <ul>
          <li>Standard check-in time is 2:00 PM (14:00) and check-out is 12:00 PM (noon).</li>
          <li>Early check-in and late check-out are subject to availability and may incur additional charges.</li>
          <li>A valid government-issued ID is required at check-in.</li>
          <li>Your digital QR pass (available in your profile) may be used for express check-in.</li>
        </ul>
      </section>

      <section>
        <h2>7. Guest Conduct</h2>
        <p>Guests are expected to respect hotel property, staff, and other guests. SATAAB Hotel reserves the right to terminate a stay without refund in cases of misconduct, damage to property, or violation of local laws.</p>
      </section>

      <section>
        <h2>8. Intellectual Property</h2>
        <p>All content on this platform — including logos, images, text, and software — is the property of SATAAB Hotel and may not be reproduced or distributed without written permission.</p>
      </section>

      <section>
        <h2>9. Limitation of Liability</h2>
        <p>SATAAB Hotel shall not be liable for any indirect, incidental, or consequential damages arising from the use of this Service, including but not limited to loss of data, revenue, or personal property. Our total liability shall not exceed the amount paid for the booking in question.</p>
      </section>

      <section>
        <h2>10. Governing Law</h2>
        <p>These Terms are governed by the laws of the Federal Democratic Republic of Ethiopia. Any disputes shall be resolved in the courts of Addis Ababa, Ethiopia.</p>
      </section>

      <section>
        <h2>11. Changes to Terms</h2>
        <p>We may update these Terms at any time. Continued use of the Service after changes constitutes acceptance of the new Terms. We will notify registered users of material changes via email.</p>
      </section>

      <section>
        <h2>12. Contact</h2>
        <p>For questions about these Terms, contact us at <a href="mailto:semredemssie83@gmail.com">semredemssie83@gmail.com</a> or visit our front desk at Addis Ababa, Ethiopia.</p>
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
</style>
