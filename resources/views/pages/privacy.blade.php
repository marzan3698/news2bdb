@extends('layouts.frontend')

@section('title', 'Privacy Policy')

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
                <h1>Privacy Policy</h1>
                <p><strong>Last updated:</strong> {{ date('F d, Y') }}</p>

                <p>Welcome to BDB News ("we", "our", or "us"). We are committed to protecting your personal information and your right to privacy. If you have any questions or concerns about this privacy notice or our practices with regard to your personal information, please contact us.</p>

                <h2>1. Information We Collect</h2>
                <p>We collect personal information that you voluntarily provide to us when you register on the website, express an interest in obtaining information about us or our products and services, or otherwise when you contact us. This includes:</p>
                <ul>
                    <li>Name and Contact Data (e.g., email address, phone number).</li>
                    <li>Credentials (e.g., passwords and security information used for authentication).</li>
                    <li>Information collected automatically (e.g., IP address, browser and device characteristics, operating system, language preferences) when you visit our site.</li>
                </ul>

                <h2>2. How We Use Your Information</h2>
                <p>We use personal information collected via our website for a variety of business purposes described below. We process your personal information for these purposes in reliance on our legitimate business interests, in order to enter into or perform a contract with you, with your consent, and/or for compliance with our legal obligations.</p>
                <ul>
                    <li>To facilitate account creation and logon process.</li>
                    <li>To send you administrative information.</li>
                    <li>To post testimonials.</li>
                    <li>To deliver targeted advertising to you.</li>
                    <li>To administer prize draws and competitions.</li>
                    <li>To request feedback and to contact you about your use of our website.</li>
                </ul>

                <h2>3. Will Your Information Be Shared With Anyone?</h2>
                <p>We only share and disclose your information in the following situations:</p>
                <ul>
                    <li><strong>Compliance with Laws:</strong> We may disclose your information where we are legally required to do so in order to comply with applicable law, governmental requests, a judicial proceeding, court order, or legal process.</li>
                    <li><strong>Vital Interests and Legal Rights:</strong> We may disclose your information where we believe it is necessary to investigate, prevent, or take action regarding potential violations of our policies, suspected fraud, situations involving potential threats to the safety of any person and illegal activities, or as evidence in litigation in which we are involved.</li>
                </ul>

                <h2>4. Cookies and Similar Technologies</h2>
                <p>We may use cookies and similar tracking technologies (like web beacons and pixels) to access or store information. Specific information about how we use such technologies and how you can refuse certain cookies is set out in our Cookie Policy.</p>

                <h2>5. Third-Party Websites and Social Media Logins</h2>
                <p>Our website may offer you the ability to register and login using your third-party social media account details (like your Facebook logins). Where you choose to do this, we will receive certain profile information about you from your social media provider.</p>

                <h2>6. How Long Do We Keep Your Information?</h2>
                <p>We will only keep your personal information for as long as it is necessary for the purposes set out in this privacy notice, unless a longer retention period is required or permitted by law.</p>

                <h2>7. Contact Us</h2>
                <p>If you have questions or comments about this notice, you may email us or contact us by post at our designated address.</p>
            </div>
        </div>
    </div>
</div>
@endsection
