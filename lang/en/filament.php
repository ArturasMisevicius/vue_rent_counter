<?php

declare(strict_types=1);

return [
    'widgets' => [
        'activity_details' => [
            'title' => 'Action Information',
            'action' => 'Action',
            'resource' => 'Resource',
            'user' => 'User',
            'organization' => 'Organization',
            'timestamp' => 'Timestamp',
            'ip_address' => 'IP Address',
            'changes' => 'Changes',
            'before' => 'Before',
            'after' => 'After',
            'system' => 'System',
            'not_available' => 'N/A',
        ],
    ],
    'resources' => [
        'subscription_usage' => [
            'usage_title' => 'Resource Usage',
            'properties' => 'Properties',
            'tenants' => 'Tenants',
            'approaching_limit' => '⚠️ Approaching limit - consider upgrading plan',
            'subscription_details' => 'Subscription Details',
            'plan_type' => 'Plan Type',
            'status' => 'Status',
            'start_date' => 'Start Date',
            'expiry_date' => 'Expiry Date',
            'days_left' => '(:days days left)',
            'expires_today' => '(expires today)',
            'expired_days_ago' => '(expired :days days ago)',
            'limit_warning_title' => 'Resource Limit Warning',
            'limit_warning_body' => 'This organization is approaching their subscription limits. Consider upgrading their plan to avoid service interruption.',
        ],
    ],
    'pages' => [
        'dashboard' => [
            'welcome' => 'Welcome back, :name!',
            'admin_description' => 'Manage your properties, tenants, and billing from this dashboard.',
            'manager_description' => 'Monitor meter readings, invoices, and property operations.',
            'tenant_description' => 'View your property details, meter readings, and invoices.',
            'quick_actions' => 'Quick Actions',
            'cards' => [
                'properties' => [
                    'title' => 'Properties',
                    'description' => 'Manage properties',
                ],
                'buildings' => [
                    'title' => 'Buildings',
                    'description' => 'Manage buildings',
                ],
                'invoices' => [
                    'title' => 'Invoices',
                    'description' => 'Manage invoices',
                ],
                'users' => [
                    'title' => 'Users',
                    'description' => 'Manage users',
                ],
            ],
            'recent_activity_title' => 'Recent Activity',
            'recent_activity_body' => 'Activity tracking coming soon...',
        ],
        'privacy' => [
            'title' => 'Privacy Policy',
            'last_updated' => 'Last updated: :date',
            'sections' => [
                'introduction' => [
                    'title' => '1. Introduction',
                    'body' => 'This Privacy Policy describes how we collect, use, and protect your personal information when you use our property management and billing system. We are committed to protecting your privacy and ensuring compliance with applicable data protection laws, including the General Data Protection Regulation (GDPR).',
                ],
                'information' => [
                    'title' => '2. Information We Collect',
                    'subsections' => [
                        [
                            'title' => '2.1 Personal Information',
                            'body' => 'We collect the following types of personal information:',
                            'items' => [
                                '<strong>Account Information:</strong> Name, email address, password (encrypted), role, and organization details',
                                '<strong>Property Information:</strong> Property addresses, types, and area measurements',
                                '<strong>Billing Information:</strong> Meter readings, invoices, payment status, and billing periods',
                                '<strong>Usage Data:</strong> Login timestamps, IP addresses, and system activity logs',
                            ],
                        ],
                        [
                            'title' => '2.2 Automatically Collected Information',
                            'body' => 'We automatically collect certain information when you use our system:',
                            'items' => [
                                'IP address and browser information',
                                'Session data and authentication tokens',
                                'System logs and audit trails',
                            ],
                        ],
                    ],
                ],
                'usage' => [
                    'title' => '3. How We Use Your Information',
                    'body' => 'We use your personal information for the following purposes:',
                    'items' => [
                        '<strong>Service Provision:</strong> To provide property management and billing services',
                        '<strong>Authentication:</strong> To verify your identity and manage access to the system',
                        '<strong>Billing:</strong> To generate invoices, track payments, and manage meter readings',
                        '<strong>Communication:</strong> To send important notifications and updates',
                        '<strong>Security:</strong> To monitor for unauthorized access and maintain system security',
                        '<strong>Compliance:</strong> To comply with legal obligations and regulatory requirements',
                    ],
                ],
                'sharing' => [
                    'title' => '4. Data Sharing and Disclosure',
                    'body' => 'We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:',
                    'items' => [
                        '<strong>Service Providers:</strong> With trusted service providers who assist in operating our system',
                        '<strong>Legal Requirements:</strong> When required by law or to protect our legal rights',
                        '<strong>Business Transfers:</strong> In connection with a merger, acquisition, or sale of assets',
                        '<strong>With Your Consent:</strong> When you have explicitly consented to the sharing',
                    ],
                ],
                'security' => [
                    'title' => '5. Data Security',
                    'body' => 'We implement appropriate technical and organizational measures to protect your personal information, including:',
                    'items' => [
                        'Encryption of passwords and sensitive data',
                        'Secure authentication and session management',
                        'Regular security audits and updates',
                        'Access controls and role-based permissions',
                        'Audit logging of all data access and modifications',
                    ],
                ],
                'retention' => [
                    'title' => '6. Data Retention',
                    'body' => 'We retain your personal information for as long as necessary to:',
                    'items' => [
                        'Provide our services to you',
                        'Comply with legal obligations',
                        'Resolve disputes and enforce agreements',
                        'Maintain audit trails for security and compliance',
                    ],
                    'note' => 'When data is no longer needed, we securely delete or anonymize it in accordance with our data retention policies.',
                ],
                'rights' => [
                    'title' => '7. Your Rights',
                    'body' => 'Under GDPR and other applicable data protection laws, you have the following rights:',
                    'items' => [
                        '<strong>Right to Access:</strong> Request a copy of your personal data',
                        '<strong>Right to Rectification:</strong> Correct inaccurate or incomplete data',
                        '<strong>Right to Erasure:</strong> Request deletion of your personal data',
                        '<strong>Right to Restrict Processing:</strong> Limit how we use your data',
                        '<strong>Right to Data Portability:</strong> Receive your data in a structured format',
                        '<strong>Right to Object:</strong> Object to certain types of data processing',
                        '<strong>Right to Withdraw Consent:</strong> Withdraw consent for data processing',
                    ],
                    'note' => 'To exercise these rights, please contact us using the information provided in Section 9.',
                ],
                'cookies' => [
                    'title' => '8. Cookies and Tracking',
                    'body' => 'We use session cookies and authentication tokens to maintain your login session and ensure system security. These are essential for the system to function properly and cannot be disabled.',
                ],
                'contact' => [
                    'title' => '9. Contact Us',
                    'body' => 'If you have questions about this Privacy Policy or wish to exercise your data protection rights, please contact us:',
                    'items' => [
                        '<strong>Email:</strong> privacy@example.com',
                        '<strong>Address:</strong> [Your Company Address]',
                    ],
                ],
                'changes' => [
                    'title' => '10. Changes to This Policy',
                    'body' => 'We may update this Privacy Policy from time to time. We will notify you of any material changes by posting the new policy on this page and updating the "Last updated" date.',
                ],
            ],
        ],
        'terms' => [
            'title' => 'Terms of Service',
            'last_updated' => 'Last updated: :date',
            'sections' => [
                'acceptance' => [
                    'title' => '1. Acceptance of Terms',
                    'body' => 'By accessing and using this property management and billing system, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to these Terms of Service, please do not use the system.',
                ],
                'description' => [
                    'title' => '2. Description of Service',
                    'body' => 'This system provides property management and utility billing services, including:',
                    'items' => [
                        'Property and building management',
                        'Meter reading tracking and management',
                        'Invoice generation and billing',
                        'Tenant and user management',
                        'Reporting and analytics',
                    ],
                ],
                'accounts' => [
                    'title' => '3. User Accounts',
                    'subsections' => [
                        [
                            'title' => '3.1 Account Creation',
                            'body' => 'To use the system, you must create an account with accurate and complete information. You are responsible for maintaining the confidentiality of your account credentials.',
                        ],
                        [
                            'title' => '3.2 Account Security',
                            'body' => 'You are responsible for:',
                            'items' => [
                                'Maintaining the security of your password',
                                'All activities that occur under your account',
                                'Notifying us immediately of any unauthorized access',
                            ],
                        ],
                        [
                            'title' => '3.3 Account Termination',
                            'body' => 'We reserve the right to suspend or terminate your account if you violate these Terms of Service or engage in any fraudulent, illegal, or harmful activity.',
                        ],
                    ],
                ],
                'acceptable_use' => [
                    'title' => '4. Acceptable Use',
                    'body' => 'You agree not to:',
                    'items' => [
                        'Use the system for any illegal or unauthorized purpose',
                        'Attempt to gain unauthorized access to the system or other accounts',
                        'Interfere with or disrupt the system\'s security or functionality',
                        'Upload malicious code, viruses, or harmful content',
                        'Impersonate any person or entity',
                        'Violate any applicable laws or regulations',
                        'Access data belonging to other tenants or organizations without authorization',
                    ],
                ],
                'accuracy' => [
                    'title' => '5. Data Accuracy',
                    'body' => 'You are responsible for ensuring that all data you enter into the system is accurate, complete, and up-to-date. We are not liable for any errors or omissions in data you provide.',
                ],
                'intellectual_property' => [
                    'title' => '6. Intellectual Property',
                    'body' => 'The system and all its content, features, and functionality are owned by us and are protected by copyright, trademark, and other intellectual property laws. You may not reproduce, distribute, or create derivative works without our express written permission.',
                ],
                'availability' => [
                    'title' => '7. Service Availability',
                    'body' => 'We strive to maintain high availability of the system but do not guarantee uninterrupted access. We reserve the right to:',
                    'items' => [
                        'Perform scheduled maintenance',
                        'Make system updates and improvements',
                        'Suspend service in case of security threats or technical issues',
                    ],
                ],
                'backup' => [
                    'title' => '8. Data Backup and Recovery',
                    'body' => 'While we implement regular backups and data protection measures, you are responsible for maintaining your own backups of critical data. We are not liable for data loss due to system failures, user error, or other causes.',
                ],
                'liability' => [
                    'title' => '9. Limitation of Liability',
                    'body' => 'To the maximum extent permitted by law, we shall not be liable for any indirect, incidental, special, consequential, or punitive damages, including but not limited to loss of profits, data, or business opportunities, arising from your use of the system.',
                ],
                'indemnification' => [
                    'title' => '10. Indemnification',
                    'body' => 'You agree to indemnify and hold us harmless from any claims, damages, losses, or expenses (including legal fees) arising from your use of the system, violation of these Terms, or infringement of any rights of another party.',
                ],
                'privacy' => [
                    'title' => '11. Privacy',
                    'body' => 'Your use of the system is also governed by our Privacy Policy. Please review our Privacy Policy to understand how we collect, use, and protect your information.',
                ],
                'modifications' => [
                    'title' => '12. Modifications to Terms',
                    'body' => 'We reserve the right to modify these Terms of Service at any time. We will notify users of material changes. Your continued use of the system after changes constitute acceptance of the modified terms.',
                ],
                'law' => [
                    'title' => '13. Governing Law',
                    'body' => 'These Terms of Service shall be governed by and construed in accordance with the laws of [Your Jurisdiction], without regard to its conflict of law provisions.',
                ],
                'contact' => [
                    'title' => '14. Contact Information',
                    'body' => 'If you have questions about these Terms of Service, please contact us:',
                    'items' => [
                        '<strong>Email:</strong> support@example.com',
                        '<strong>Address:</strong> [Your Company Address]',
                    ],
                ],
            ],
        ],
        'gdpr' => [
            'title' => 'GDPR Compliance',
            'last_updated' => 'Last updated: :date',
            'sections' => [
                'overview' => [
                    'title' => '1. GDPR Overview',
                    'body' => 'The General Data Protection Regulation (GDPR) is a comprehensive data protection law that came into effect on May 25, 2018. It applies to all organizations that process personal data of individuals in the European Union (EU). We are committed to full compliance with GDPR requirements.',
                ],
                'measures' => [
                    'title' => '2. Our GDPR Compliance Measures',
                    'subsections' => [
                        [
                            'title' => '2.1 Lawful Basis for Processing',
                            'body' => 'We process personal data based on the following lawful bases:',
                            'items' => [
                                '<strong>Contract Performance:</strong> To provide property management and billing services',
                                '<strong>Legal Obligation:</strong> To comply with accounting and tax requirements',
                                '<strong>Legitimate Interests:</strong> For system security, fraud prevention, and service improvement',
                                '<strong>Consent:</strong> Where explicitly provided by users',
                            ],
                        ],
                        [
                            'title' => '2.2 Data Minimization',
                            'body' => 'We only collect and process personal data that is:',
                            'items' => [
                                'Necessary for the purposes stated',
                                'Adequate and relevant to our services',
                                'Limited to what is required',
                            ],
                        ],
                        [
                            'title' => '2.3 Purpose Limitation',
                            'body' => 'Personal data is collected for specified, explicit, and legitimate purposes and is not processed in a manner incompatible with those purposes.',
                        ],
                        [
                            'title' => '2.4 Storage Limitation',
                            'body' => 'Personal data is kept in a form that permits identification for no longer than necessary for the purposes for which it is processed. We have implemented data retention policies that specify retention periods for different types of data.',
                        ],
                        [
                            'title' => '2.5 Accuracy',
                            'body' => 'We take reasonable steps to ensure personal data is accurate and kept up-to-date. Users can update their information through the system interface.',
                        ],
                        [
                            'title' => '2.6 Integrity and Confidentiality',
                            'body' => 'We implement appropriate technical and organizational measures to ensure:',
                            'items' => [
                                'Encryption of sensitive data in transit and at rest',
                                'Access controls and role-based permissions',
                                'Regular security audits and vulnerability assessments',
                                'Employee training on data protection',
                                'Incident response procedures',
                            ],
                        ],
                        [
                            'title' => '2.7 Accountability',
                            'body' => 'We maintain documentation of our data processing activities, including:',
                            'items' => [
                                'Records of processing activities',
                                'Data protection impact assessments',
                                'Security incident logs',
                                'Data breach notification procedures',
                            ],
                        ],
                    ],
                ],
                'rights' => [
                    'title' => '3. Individual Rights Under GDPR',
                    'body' => 'We respect and facilitate the exercise of the following rights:',
                    'subsections' => [
                        [
                            'title' => '3.1 Right to Access (Article 15)',
                            'body' => 'You have the right to obtain confirmation as to whether we process your personal data and to access that data, along with information about how it is processed.',
                        ],
                        [
                            'title' => '3.2 Right to Rectification (Article 16)',
                            'body' => 'You have the right to have inaccurate personal data corrected and incomplete data completed.',
                        ],
                        [
                            'title' => '3.3 Right to Erasure (Article 17) - "Right to be Forgotten"',
                            'body' => 'You have the right to request deletion of your personal data when:',
                            'items' => [
                                'The data is no longer necessary for the original purpose',
                                'You withdraw consent and there is no other legal basis',
                                'The data has been unlawfully processed',
                                'Deletion is required to comply with a legal obligation',
                            ],
                        ],
                        [
                            'title' => '3.4 Right to Restrict Processing (Article 18)',
                            'body' => 'You have the right to restrict processing of your personal data in certain circumstances, such as when you contest the accuracy of the data or object to processing.',
                        ],
                        [
                            'title' => '3.5 Right to Data Portability (Article 20)',
                            'body' => 'You have the right to receive your personal data in a structured, commonly used, and machine-readable format and to transmit that data to another controller.',
                        ],
                        [
                            'title' => '3.6 Right to Object (Article 21)',
                            'body' => 'You have the right to object to processing of your personal data based on legitimate interests or for direct marketing purposes.',
                        ],
                        [
                            'title' => '3.7 Rights Related to Automated Decision-Making (Article 22)',
                            'body' => 'You have the right not to be subject to decisions based solely on automated processing, including profiling, that produce legal effects or similarly significantly affect you.',
                        ],
                    ],
                ],
                'records' => [
                    'title' => '4. Data Processing Records',
                    'body' => 'We maintain records of our data processing activities, including:',
                    'items' => [
                        'Categories of personal data processed',
                        'Purposes of processing',
                        'Categories of data subjects',
                        'Recipients of personal data',
                        'Data retention periods',
                        'Security measures implemented',
                    ],
                ],
                'breach' => [
                    'title' => '5. Data Breach Notification',
                    'body' => 'In the event of a personal data breach that is likely to result in a high risk to your rights and freedoms, we will:',
                    'items' => [
                        'Notify the relevant supervisory authority within 72 hours',
                        'Inform affected individuals without undue delay',
                        'Provide clear information about the nature of the breach',
                        'Explain the likely consequences and measures taken',
                    ],
                ],
                'dpo' => [
                    'title' => '6. Data Protection Officer (DPO)',
                    'body' => 'We have appointed a Data Protection Officer to oversee our GDPR compliance. You can contact our DPO for any data protection inquiries:',
                    'items' => [
                        '<strong>Email:</strong> dpo@example.com',
                        '<strong>Address:</strong> [Your Company Address]',
                    ],
                ],
                'processors' => [
                    'title' => '7. Third-Party Processors',
                    'body' => 'When we use third-party service providers to process personal data, we ensure they:',
                    'items' => [
                        'Provide adequate guarantees for data protection',
                        'Are bound by data processing agreements',
                        'Comply with GDPR requirements',
                        'Implement appropriate security measures',
                    ],
                ],
                'transfers' => [
                    'title' => '8. International Data Transfers',
                    'body' => 'If we transfer personal data outside the EU/EEA, we ensure appropriate safeguards are in place, such as:',
                    'items' => [
                        'Standard Contractual Clauses (SCCs)',
                        'Adequacy decisions by the European Commission',
                        'Binding Corporate Rules (BCRs)',
                    ],
                ],
                'exercising' => [
                    'title' => '9. Exercising Your Rights',
                    'body' => 'To exercise any of your GDPR rights, please contact us:',
                    'items' => [
                        '<strong>Email:</strong> privacy@example.com',
                        '<strong>Subject Line:</strong> "GDPR Request - [Your Request Type]"',
                    ],
                    'note' => 'We will respond to your request within one month. If your request is complex, we may extend this period by an additional two months, and we will inform you of the extension.',
                ],
                'authority' => [
                    'title' => '10. Supervisory Authority',
                    'body' => 'If you believe we have not adequately addressed your data protection concerns, you have the right to lodge a complaint with your local supervisory authority. For EU residents, you can find your supervisory authority at: <a href="https://edpb.europa.eu/about-edpb/board/members_en" target="_blank" rel="noopener">European Data Protection Board</a>',
                ],
                'updates' => [
                    'title' => '11. Updates to This Policy',
                    'body' => 'We may update this GDPR Compliance information to reflect changes in our practices or legal requirements. We will notify users of any material changes.',
                ],
            ],
        ],
    ],
];
