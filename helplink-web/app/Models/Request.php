<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    protected $table = 'requests';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * =========================
     * MASS ASSIGNMENT
     * =========================
     */
    protected $fillable = [
        'user_id',
        'item_name',
        'description',
        'category',
        'address',
        'latitude',
        'longitude',
        'image',            // gambar sokongan
        'document',         // dokumen sokongan
        'ocr_text',         // (LEGACY – boleh kekal)
        'status',           // pending, approved, rejected, fulfilled


        'admin_remark',
        
        // 🔥 AI FIELDS
        'ai_document_type',
        'ai_summary',
        'ai_extracted_data',
        'ai_confidence',
    ];

    /**
     * =========================
     * CASTING
     * =========================
     */
    protected $casts = [
        'ai_extracted_data' => 'array',
        'latitude'  => 'float',
        'longitude' => 'float',
        'ai_confidence' => 'integer',
    ];

    /**
     * =========================
     * RELATIONSHIPS
     * =========================
     */

    // 🔗 Request dibuat oleh User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 🔗 Satu request boleh ada banyak gambar sokongan
    public function images()
    {
        return $this->hasMany(RequestImage::class, 'request_id');
    }

    // 🔗 Request boleh ada banyak claim (penderma)
    public function claimRequests()
    {
        return $this->hasMany(ClaimRequest::class, 'request_id');
    }

    // 🔗 Claim yang telah selesai (fulfilled)
    public function completedClaim()
    {
        return $this->hasOne(ClaimRequest::class, 'request_id')
            ->where('status', 'fulfilled');
    }

    /**
     * =========================
     * HELPER ATTRIBUTES (OPTIONAL, UI CANTIK)
     * =========================
     */

    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'approved'  => 'success',
            'rejected'  => 'danger',
            'fulfilled' => 'primary',
            default     => 'secondary',
        };
    }
}
