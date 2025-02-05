<?php

namespace Database\Seeders;

use App\Models\LegalDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LegalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        // Terms of Service
        LegalDocument::create([
            'title' => 'Terms of Service',
            'slug' => 'terms-of-service',
            'type' => 'terms',
            'content' => $this->getTermsContent(),
            'is_active' => true,
            'version' => '1.0',
            'published_at' => Carbon::now(),
        ]);

        // Privacy Policy
        LegalDocument::create([
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'type' => 'privacy',
            'content' => $this->getPrivacyContent(),
            'is_active' => true,
            'version' => '1.0',
            'published_at' => Carbon::now(),
        ]);
    }

    private function getTermsContent(): string
    {
        return <<<'EOT'
        {
          "type": "doc",
          "content": [
            {
              "type": "heading",
              "attrs": {"level": 1},
              "content": [{"type": "text", "text": "Terms of Service"}]
            },
            {
              "type": "paragraph",
              "content": [{"type": "text", "text": "Last updated: " }, {"type": "text", "marks": [{"type": "bold"}], "text": "2024"}]
            },
            {
              "type": "heading",
              "attrs": {"level": 2},
              "content": [{"type": "text", "text": "1. Agreement to Terms"}]
            },
            {
              "type": "paragraph",
              "content": [{"type": "text", "text": "By accessing or using our school management system, you agree to be bound by these Terms of Service and all applicable laws and regulations."}]
            },
            {
              "type": "heading",
              "attrs": {"level": 2},
              "content": [{"type": "text", "text": "2. Use License"}]
            },
            {
              "type": "paragraph",
              "content": [{"type": "text", "text": "We grant you a limited, non-exclusive, non-transferable license to use our system for your school's administrative purposes. This license is subject to these Terms of Service."}]
            },
            {
              "type": "heading",
              "attrs": {"level": 2},
              "content": [{"type": "text", "text": "3. Service Description"}]
            },
            {
              "type": "paragraph",
              "content": [{"type": "text", "text": "Our system provides tools for managing student records, attendance, grades, schedules, and other school-related administrative tasks."}]
            },
            {
              "type": "heading",
              "attrs": {"level": 2},
              "content": [{"type": "text", "text": "4. User Obligations"}]
            },
            {
              "type": "paragraph",
              "content": [{"type": "text", "text": "You agree to:"}]
            },
            {
              "type": "bulletList",
              "content": [
                {
                  "type": "listItem",
                  "content": [{"type": "paragraph", "content": [{"type": "text", "text": "Provide accurate and complete information"}]}]
                },
                {
                  "type": "listItem",
                  "content": [{"type": "paragraph", "content": [{"type": "text", "text": "Maintain the security of your account"}]}]
                },
                {
                  "type": "listItem",
                  "content": [{"type": "paragraph", "content": [{"type": "text", "text": "Comply with all applicable laws and regulations"}]}]
                }
              ]
            },
            {
              "type": "heading",
              "attrs": {"level": 2},
              "content": [{"type": "text", "text": "5. Payment Terms"}]
            },
            {
              "type": "paragraph",
              "content": [{"type": "text", "text": "Subscription fees are billed according to your selected plan. All fees are non-refundable unless otherwise required by law."}]
            }
          ]
        }
        EOT;
    }

    private function getPrivacyContent(): string
    {
        return <<<'EOT'
        {
          "type": "doc",
          "content": [
            {
              "type": "heading",
              "attrs": {"level": 1},
              "content": [{"type": "text", "text": "Privacy Policy"}]
            },
            {
              "type": "paragraph",
              "content": [{"type": "text", "text": "Last updated: " }, {"type": "text", "marks": [{"type": "bold"}], "text": "2024"}]
            },
            {
              "type": "heading",
              "attrs": {"level": 2},
              "content": [{"type": "text", "text": "1. Information We Collect"}]
            },
            {
              "type": "paragraph",
              "content": [{"type": "text", "text": "We collect information that you provide directly to us, including:"}]
            },
            {
              "type": "bulletList",
              "content": [
                {
                  "type": "listItem",
                  "content": [{"type": "paragraph", "content": [{"type": "text", "text": "School administrative information"}]}]
                },
                {
                  "type": "listItem",
                  "content": [{"type": "paragraph", "content": [{"type": "text", "text": "Student and staff records"}]}]
                },
                {
                  "type": "listItem",
                  "content": [{"type": "paragraph", "content": [{"type": "text", "text": "Contact information"}]}]
                }
              ]
            },
            {
              "type": "heading",
              "attrs": {"level": 2},
              "content": [{"type": "text", "text": "2. How We Use Your Information"}]
            },
            {
              "type": "paragraph",
              "content": [{"type": "text", "text": "We use the information we collect to:"}]
            },
            {
              "type": "bulletList",
              "content": [
                {
                  "type": "listItem",
                  "content": [{"type": "paragraph", "content": [{"type": "text", "text": "Provide and maintain our services"}]}]
                },
                {
                  "type": "listItem",
                  "content": [{"type": "paragraph", "content": [{"type": "text", "text": "Process your transactions"}]}]
                },
                {
                  "type": "listItem",
                  "content": [{"type": "paragraph", "content": [{"type": "text", "text": "Send you technical notices and updates"}]}]
                }
              ]
            },
            {
              "type": "heading",
              "attrs": {"level": 2},
              "content": [{"type": "text", "text": "3. Data Security"}]
            },
            {
              "type": "paragraph",
              "content": [{"type": "text", "text": "We implement appropriate security measures to protect your personal information."}]
            }
          ]
        }
        EOT;
    }
}
