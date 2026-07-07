@extends('layouts.frontend')

@section('title', 'Terms of Service')

@push('css')
<style>
    .legal-content {
        background: #fff;
        padding: 40px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin: 40px 0;
    }
    .legal-content h1 {
        font-size: 2.5rem;
        margin-bottom: 30px;
        color: #333;
        font-weight: 700;
    }
    .legal-content h2 {
        font-size: 1.5rem;
        margin-top: 30px;
        margin-bottom: 15px;
        color: #444;
        font-weight: 600;
    }
    .legal-content p, .legal-content li {
        font-size: 1.1rem;
        line-height: 1.8;
        color: #555;
    }
    .legal-content ul {
        margin-bottom: 20px;
        padding-left: 20px;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="legal-content">
                <h1>Terms of Service</h1>
                <p><strong>Last updated:</strong> {{ date('F d, Y') }}</p>

                <p>These Terms of Service ("Terms") govern your use of the BDB News website and services. By accessing or using our website, you agree to be bound by these Terms and our Privacy Policy.</p>

                <h2>1. Acceptance of Terms</h2>
                <p>By accessing this website, you agree that you have read, understood, and agree to be bound by these Terms. If you do not agree with any part of these Terms, you must not use our website or services.</p>

                <h2>2. User Accounts</h2>
                <p>When you create an account with us, you must provide accurate, complete, and up-to-date information at all times. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account on our service.</p>
                <ul>
                    <li>You are responsible for safeguarding the password that you use to access the service and for any activities or actions under your password.</li>
                    <li>You agree not to disclose your password to any third party. You must notify us immediately upon becoming aware of any breach of security or unauthorized use of your account.</li>
                </ul>

                <h2>3. Intellectual Property</h2>
                <p>The service and its original content, features, and functionality are and will remain the exclusive property of BDB News and its licensors. The service is protected by copyright, trademark, and other laws.</p>

                <h2>4. Links To Other Web Sites</h2>
                <p>Our Service may contain links to third-party web sites or services that are not owned or controlled by BDB News.</p>
                <p>We have no control over, and assume no responsibility for, the content, privacy policies, or practices of any third party web sites or services. You further acknowledge and agree that BDB News shall not be responsible or liable, directly or indirectly, for any damage or loss caused or alleged to be caused by or in connection with use of or reliance on any such content, goods or services available on or through any such web sites or services.</p>

                <h2>5. Termination</h2>
                <p>We may terminate or suspend access to our service immediately, without prior notice or liability, for any reason whatsoever, including without limitation if you breach the Terms.</p>
                <p>All provisions of the Terms which by their nature should survive termination shall survive termination, including, without limitation, ownership provisions, warranty disclaimers, indemnity and limitations of liability.</p>

                <h2>6. Limitation of Liability</h2>
                <p>In no event shall BDB News, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from your access to or use of or inability to access or use the Service.</p>

                <h2>7. Changes</h2>
                <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. What constitutes a material change will be determined at our sole discretion.</p>
                <p>By continuing to access or use our Service after those revisions become effective, you agree to be bound by the revised terms. If you do not agree to the new terms, please stop using the Service.</p>

                <h2>8. Contact Us</h2>
                <p>If you have any questions about these Terms, please contact us.</p>
            </div>
        </div>
    </div>
</div>
@endsection
