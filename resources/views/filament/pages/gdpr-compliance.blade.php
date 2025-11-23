<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose max-w-none dark:prose-invert">
            <h1>GDPR Compliance</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Last updated: {{ now()->format('F j, Y') }}</p>

            <section>
                <h2>1. GDPR Overview</h2>
                <p>
                    The General Data Protection Regulation (GDPR) is a comprehensive data protection
                    law that came into effect on May 25, 2018. It applies to all organizations that process
                    personal data of individuals in the European Union (EU). We are committed to full
                    compliance with GDPR requirements.
                </p>
            </section>

            <section>
                <h2>2. Our GDPR Compliance Measures</h2>
                
                <h3>2.1 Lawful Basis for Processing</h3>
                <p>We process personal data based on the following lawful bases:</p>
                <ul>
                    <li><strong>Contract Performance:</strong> To provide property management and billing services</li>
                    <li><strong>Legal Obligation:</strong> To comply with accounting and tax requirements</li>
                    <li><strong>Legitimate Interests:</strong> For system security, fraud prevention, and service improvement</li>
                    <li><strong>Consent:</strong> Where explicitly provided by users</li>
                </ul>

                <h3>2.2 Data Minimization</h3>
                <p>We only collect and process personal data that is:</p>
                <ul>
                    <li>Necessary for the purposes stated</li>
                    <li>Adequate and relevant to our services</li>
                    <li>Limited to what is required</li>
                </ul>

                <h3>2.3 Purpose Limitation</h3>
                <p>Personal data is collected for specified, explicit, and legitimate purposes and
                is not processed in a manner incompatible with those purposes.</p>

                <h3>2.4 Storage Limitation</h3>
                <p>Personal data is kept in a form that permits identification for no longer than
                necessary for the purposes for which it is processed. We have implemented data retention
                policies that specify retention periods for different types of data.</p>

                <h3>2.5 Accuracy</h3>
                <p>We take reasonable steps to ensure personal data is accurate and kept up-to-date.
                Users can update their information through the system interface.</p>

                <h3>2.6 Integrity and Confidentiality</h3>
                <p>We implement appropriate technical and organizational measures to ensure:</p>
                <ul>
                    <li>Encryption of sensitive data in transit and at rest</li>
                    <li>Access controls and role-based permissions</li>
                    <li>Regular security audits and vulnerability assessments</li>
                    <li>Employee training on data protection</li>
                    <li>Incident response procedures</li>
                </ul>

                <h3>2.7 Accountability</h3>
                <p>We maintain documentation of our data processing activities, including:</p>
                <ul>
                    <li>Records of processing activities</li>
                    <li>Data protection impact assessments</li>
                    <li>Security incident logs</li>
                    <li>Data breach notification procedures</li>
                </ul>
            </section>

            <section>
                <h2>3. Individual Rights Under GDPR</h2>
                <p>We respect and facilitate the exercise of the following rights:</p>

                <h3>3.1 Right to Access (Article 15)</h3>
                <p>You have the right to obtain confirmation as to whether we process your personal data
                and to access that data, along with information about how it is processed.</p>

                <h3>3.2 Right to Rectification (Article 16)</h3>
                <p>You have the right to have inaccurate personal data corrected and incomplete data
                completed.</p>

                <h3>3.3 Right to Erasure (Article 17) - "Right to be Forgotten"</h3>
                <p>You have the right to request deletion of your personal data when:</p>
                <ul>
                    <li>The data is no longer necessary for the original purpose</li>
                    <li>You withdraw consent and there is no other legal basis</li>
                    <li>The data has been unlawfully processed</li>
                    <li>Deletion is required to comply with a legal obligation</li>
                </ul>

                <h3>3.4 Right to Restrict Processing (Article 18)</h3>
                <p>You have the right to restrict processing of your personal data in certain circumstances,
                such as when you contest the accuracy of the data or object to processing.</p>

                <h3>3.5 Right to Data Portability (Article 20)</h3>
                <p>You have the right to receive your personal data in a structured, commonly used,
                and machine-readable format and to transmit that data to another controller.</p>

                <h3>3.6 Right to Object (Article 21)</h3>
                <p>You have the right to object to processing of your personal data based on legitimate
                interests or for direct marketing purposes.</p>

                <h3>3.7 Rights Related to Automated Decision-Making (Article 22)</h3>
                <p>You have the right not to be subject to decisions based solely on automated processing,
                including profiling, that produce legal effects or similarly significantly affect you.</p>
            </section>

            <section>
                <h2>4. Data Processing Records</h2>
                <p>We maintain records of our data processing activities, including:</p>
                <ul>
                    <li>Categories of personal data processed</li>
                    <li>Purposes of processing</li>
                    <li>Categories of data subjects</li>
                    <li>Recipients of personal data</li>
                    <li>Data retention periods</li>
                    <li>Security measures implemented</li>
                </ul>
            </section>

            <section>
                <h2>5. Data Breach Notification</h2>
                <p>In the event of a personal data breach that is likely to result in a high risk to
                your rights and freedoms, we will:</p>
                <ul>
                    <li>Notify the relevant supervisory authority within 72 hours</li>
                    <li>Inform affected individuals without undue delay</li>
                    <li>Provide clear information about the nature of the breach</li>
                    <li>Explain the likely consequences and measures taken</li>
                </ul>
            </section>

            <section>
                <h2>6. Data Protection Officer (DPO)</h2>
                <p>We have appointed a Data Protection Officer to oversee our GDPR compliance. You can
                contact our DPO for any data protection inquiries:</p>
                <ul>
                    <li><strong>Email:</strong> dpo@example.com</li>
                    <li><strong>Address:</strong> [Your Company Address]</li>
                </ul>
            </section>

            <section>
                <h2>7. Third-Party Processors</h2>
                <p>When we use third-party service providers to process personal data, we ensure they:</p>
                <ul>
                    <li>Provide adequate guarantees for data protection</li>
                    <li>Are bound by data processing agreements</li>
                    <li>Comply with GDPR requirements</li>
                    <li>Implement appropriate security measures</li>
                </ul>
            </section>

            <section>
                <h2>8. International Data Transfers</h2>
                <p>If we transfer personal data outside the EU/EEA, we ensure appropriate safeguards
                are in place, such as:</p>
                <ul>
                    <li>Standard Contractual Clauses (SCCs)</li>
                    <li>Adequacy decisions by the European Commission</li>
                    <li>Binding Corporate Rules (BCRs)</li>
                </ul>
            </section>

            <section>
                <h2>9. Exercising Your Rights</h2>
                <p>To exercise any of your GDPR rights, please contact us:</p>
                <ul>
                    <li><strong>Email:</strong> privacy@example.com</li>
                    <li><strong>Subject Line:</strong> "GDPR Request - [Your Request Type]"</li>
                </ul>
                <p>We will respond to your request within one month. If your request is complex, we may
                extend this period by an additional two months, and we will inform you of the extension.</p>
            </section>

            <section>
                <h2>10. Supervisory Authority</h2>
                <p>If you believe we have not adequately addressed your data protection concerns, you
                have the right to lodge a complaint with your local supervisory authority. For EU
                residents, you can find your supervisory authority at:
                <a href="https://edpb.europa.eu/about-edpb/board/members_en" target="_blank" rel="noopener">
                    European Data Protection Board
                </a>
                </p>
            </section>

            <section>
                <h2>11. Updates to This Policy</h2>
                <p>We may update this GDPR Compliance information to reflect changes in our practices
                or legal requirements. We will notify users of any material changes.</p>
            </section>
        </div>
    </div>
</x-filament-panels::page>
