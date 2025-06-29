<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Registration untuk mengelola data pendaftaran kompetisi
 * 
 * Kelas ini menangani proses pendaftaran peserta ke kompetisi
 * termasuk status pendaftaran dan pembayaran
 */
class Registration extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara mass assignment
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'competition_id',
        'registration_number',
        'team_name',
        'team_members',
        'institution',
        'phone',
        'emergency_contact',
        'emergency_phone',
        'special_needs',
        'amount',
        'status',
        'registered_at',
        'confirmed_at',
        'cancelled_at',
        'cancelled_reason',
        'reopened_at',
        'reopened_by',
        'ticket_code',
        'qr_code',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu
     *
     * @var array<string, string>
     */
    protected $casts = [
        'team_members' => 'array',
        'registered_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reopened_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Konstanta untuk status pendaftaran
     */
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    /**
     * Boot method untuk generate registration number otomatis
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($registration) {
            if (!$registration->registration_number) {
                $registration->registration_number = $registration->generateRegistrationNumber();
            }
            
            if (!$registration->ticket_code) {
                $registration->ticket_code = $registration->generateTicketCode();
            }
        });
    }

    /**
     * Relasi dengan model User (peserta)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi dengan model Competition (kompetisi)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Relasi dengan model Payment (pembayaran)
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Relasi dengan model Submission (karya)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function submission()
    {
        return $this->hasOne(Submission::class);
    }

    /**
     * Relasi dengan model Submission (karya) - plural
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Relasi dengan model TeamMember (anggota tim)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function teamMembers()
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Scope untuk pendaftaran yang dikonfirmasi
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Scope untuk pendaftaran yang pending
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Generate nomor pendaftaran unik
     * 
     * @return string
     */
    protected function generateRegistrationNumber()
    {
        $year = date('Y');
        $month = date('m');
        
        // Format: UF2025-MM-XXXX
        $prefix = "UF{$year}-{$month}-";
        
        $lastNumber = static::where('registration_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();
            
        if ($lastNumber) {
            $number = intval(substr($lastNumber->registration_number, -4)) + 1;
        } else {
            $number = 1;
        }
        
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate kode tiket unik
     * 
     * @return string
     */
    protected function generateTicketCode()
    {
        do {
            $code = 'TICKET-' . strtoupper(substr(md5(uniqid()), 0, 8));
        } while (static::where('ticket_code', $code)->exists());
        
        return $code;
    }

    /**
     * Konfirmasi pendaftaran
     * 
     * @return void
     */
    public function confirm()
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
        
        // Generate QR Code untuk e-ticket
        $this->generateQRCode();
    }

    /**
     * Generate QR Code untuk e-ticket
     *
     * @return void
     */
    public function generateQRCode()
    {
        $qrData = [
            'type' => 'unas_fest_ticket',
            'registration_number' => $this->registration_number,
            'ticket_code' => $this->ticket_code,
            'competition_id' => $this->competition_id,
            'competition' => $this->competition->name,
            'participant' => $this->user->name,
            'participant_email' => $this->user->email,
            'issued_at' => now()->toISOString(),
            'verify_url' => route('ticket.verify', $this->ticket_code)
        ];

        $qrCodeData = json_encode($qrData);

        // Generate QR Code menggunakan SimpleSoftwareIO
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->generate($qrCodeData);

        // Simpan QR code ke storage
        $qrPath = 'qrcodes/' . $this->ticket_code . '.png';
        \Storage::disk('public')->put($qrPath, $qrCode);

        $this->update(['qr_code' => $qrPath]);
    }

    /**
     * Cek apakah pendaftaran sudah dikonfirmasi
     * 
     * @return bool
     */
    public function isConfirmed()
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /**
     * Cek apakah pendaftaran masih pending
     * 
     * @return bool
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Cek apakah pembayaran sudah berhasil
     * 
     * @return bool
     */
    public function isPaid()
    {
        return $this->payment && $this->payment->status === 'success';
    }

    /**
     * Accessor untuk URL QR Code
     * 
     * @return string|null
     */
    public function getQrCodeUrlAttribute()
    {
        if ($this->qr_code) {
            return asset('storage/' . $this->qr_code);
        }
        
        return null;
    }

    /**
     * Accessor untuk mendapatkan nama tim atau peserta
     * 
     * @return string
     */
    public function getDisplayNameAttribute()
    {
        return $this->team_name ?: $this->user->name;
    }

    /**
     * Cancel pendaftaran
     *
     * @return void
     */
    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_reason' => 'Dibatalkan oleh peserta'
        ]);
    }

    /**
     * Reopen pendaftaran yang dibatalkan (hanya admin)
     *
     * @return void
     */
    public function reopen()
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'cancelled_at' => null,
            'cancelled_reason' => null,
            'reopened_at' => now(),
            'reopened_by' => auth()->id()
        ]);
    }

    /**
     * Expire pendaftaran yang belum dibayar
     *
     * @return void
     */
    public function expire()
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Cek apakah pendaftaran sudah expired
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Cek apakah pendaftaran dibatalkan
     *
     * @return bool
     */
    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Cek apakah user bisa mendaftar lagi di kompetisi ini
     *
     * @param int $userId
     * @param int $competitionId
     * @return bool
     */
    public static function canRegisterAgain($userId, $competitionId)
    {
        $cancelledRegistration = self::where('user_id', $userId)
            ->where('competition_id', $competitionId)
            ->where('status', self::STATUS_CANCELLED)
            ->first();

        // Jika tidak ada registrasi yang dibatalkan, bisa daftar
        if (!$cancelledRegistration) {
            return true;
        }

        // Jika ada registrasi yang dibatalkan, tidak bisa daftar lagi
        // kecuali admin sudah reopen
        return false;
    }
}
