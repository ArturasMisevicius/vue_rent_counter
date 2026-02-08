<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantDocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_tenant_can_have_attachments(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $attachment = Attachment::create([
            'tenant_id' => $tenant->tenant_id,
            'attachable_id' => $tenant->id,
            'attachable_type' => Tenant::class,
            'uploaded_by' => $user->id,
            'filename' => 'test_photo.jpg',
            'original_filename' => 'tenant_photo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'disk' => 'private',
            'path' => 'tenants/photos/test_photo.jpg',
            'description' => 'Tenant photo',
            'metadata' => ['category' => 'photo'],
        ]);

        $this->assertCount(1, $tenant->attachments);
        $this->assertEquals('photo', $attachment->metadata['category']);
        $this->assertTrue($attachment->isImage());
    }

    public function test_tenant_photo_relationship(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Create a photo attachment
        Attachment::create([
            'tenant_id' => $tenant->tenant_id,
            'attachable_id' => $tenant->id,
            'attachable_type' => Tenant::class,
            'uploaded_by' => $user->id,
            'filename' => 'photo.jpg',
            'original_filename' => 'tenant_photo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'disk' => 'private',
            'path' => 'tenants/photos/photo.jpg',
            'metadata' => ['category' => 'photo'],
        ]);

        $photo = $tenant->photo();
        $this->assertNotNull($photo);
        $this->assertEquals('photo', $photo->metadata['category']);
    }

    public function test_tenant_lease_contract_relationship(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Create a contract attachment
        Attachment::create([
            'tenant_id' => $tenant->tenant_id,
            'attachable_id' => $tenant->id,
            'attachable_type' => Tenant::class,
            'uploaded_by' => $user->id,
            'filename' => 'contract.pdf',
            'original_filename' => 'lease_contract.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
            'disk' => 'private',
            'path' => 'tenants/contracts/contract.pdf',
            'metadata' => ['category' => 'contract'],
        ]);

        $contract = $tenant->leaseContract();
        $this->assertNotNull($contract);
        $this->assertEquals('contract', $contract->metadata['category']);
        $this->assertTrue($contract->isPdf());
    }

    public function test_tenant_identity_documents_relationship(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Create identity document attachments
        Attachment::create([
            'tenant_id' => $tenant->tenant_id,
            'attachable_id' => $tenant->id,
            'attachable_type' => Tenant::class,
            'uploaded_by' => $user->id,
            'filename' => 'passport.pdf',
            'original_filename' => 'passport.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'disk' => 'private',
            'path' => 'tenants/documents/passport.pdf',
            'metadata' => ['category' => 'identity'],
        ]);

        Attachment::create([
            'tenant_id' => $tenant->tenant_id,
            'attachable_id' => $tenant->id,
            'attachable_type' => Tenant::class,
            'uploaded_by' => $user->id,
            'filename' => 'id_card.jpg',
            'original_filename' => 'id_card.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 512,
            'disk' => 'private',
            'path' => 'tenants/documents/id_card.jpg',
            'metadata' => ['category' => 'identity'],
        ]);

        $identityDocs = $tenant->identityDocuments()->get();
        $this->assertCount(2, $identityDocs);
        
        foreach ($identityDocs as $doc) {
            $this->assertEquals('identity', $doc->metadata['category']);
        }
    }

    public function test_attachment_file_size_formatting(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $attachment = Attachment::create([
            'tenant_id' => $tenant->tenant_id,
            'attachable_id' => $tenant->id,
            'attachable_type' => Tenant::class,
            'uploaded_by' => $user->id,
            'filename' => 'large_file.pdf',
            'original_filename' => 'large_file.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1048576, // 1MB
            'disk' => 'private',
            'path' => 'tenants/documents/large_file.pdf',
            'metadata' => ['category' => 'document'],
        ]);

        $this->assertEquals('1 MB', $attachment->human_size);
    }
}