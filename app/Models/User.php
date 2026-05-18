<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'first_name', 'last_name', 'email', 'phone',
        'password', 'kyc_verified_at', 'avatar_path',
        'address', 'bvn_masked', 'is_admin', 'admin_role_id', 'sales_rep_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'kyc_verified_at'   => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
        ];
    }

    // ── Admin role helpers ─────────────────────────────────────────────────

    public function adminRole(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class);
    }

    /** Super admin: is_admin with no restricted role assigned. */
    public function isSuperAdmin(): bool
    {
        return $this->is_admin && is_null($this->admin_role_id);
    }

    /** Staff member: is_admin but with a restricted role. */
    public function isStaff(): bool
    {
        return $this->is_admin && !is_null($this->admin_role_id);
    }

    /** Returns true if user can perform an action (super admins can do everything). */
    public function adminCan(string $permission): bool
    {
        if (!$this->is_admin) return false;
        if ($this->isSuperAdmin()) return true;
        return $this->adminRole?->hasPermission($permission) ?? false;
    }

    /** Query scope: admin users who have a specific permission. */
    public function scopeWithAdminPermission(Builder $query, string $permission): Builder
    {
        return $query->where('is_admin', true)
            ->where(function ($q) use ($permission) {
                $q->whereNull('admin_role_id')
                  ->orWhereHas('adminRole', fn($q2) => $q2->whereJsonContains('permissions', $permission));
            });
    }

    // ── Existing relationships ─────────────────────────────────────────────

    public function ownerships(): HasMany
    {
        return $this->hasMany(Ownership::class);
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'ownerships')
            ->withPivot(['agreed_price_kobo', 'start_date', 'payment_plan', 'total_instalments', 'status'])
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function supportMessages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function reminderPreference(): HasOne
    {
        return $this->hasOne(ReminderPreference::class);
    }

    // ── New relationships ──────────────────────────────────────────────────

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(SalesRep::class);
    }

    public function loginActivities(): HasMany
    {
        return $this->hasMany(LoginActivity::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function googleCalendarToken(): HasOne
    {
        return $this->hasOne(GoogleCalendarToken::class);
    }

    public function interestRegistrations(): HasMany
    {
        return $this->hasMany(InterestRegistration::class);
    }

    // Ownerships this user belongs to as a group MEMBER (not primary owner)
    public function memberOwnerships(): BelongsToMany
    {
        return $this->belongsToMany(Ownership::class, 'ownership_members', 'user_id', 'ownership_id')
            ->withPivot(['role', 'invited_by', 'accepted_at'])
            ->wherePivotNotNull('accepted_at')
            ->withTimestamps();
    }

    // All ownerships this user can access (as owner OR accepted member)
    public function accessibleOwnerships(): \Illuminate\Database\Eloquent\Collection
    {
        $own    = $this->ownerships()->with(['property', 'unit'])->get();
        $member = $this->memberOwnerships()->with(['property', 'unit'])->get();
        return $own->merge($member)->unique('id');
    }

    // True if the user has primary-owner or co-owner access on at least one ownership.
    // Financial and viewer members return false.
    public function hasOwnerAccess(): bool
    {
        return $this->ownerships()->exists()
            || $this->memberOwnerships()->wherePivot('role', 'owner')->exists();
    }

    // True if the user is a view-only group member for this specific ownership
    public function isViewerOnly(Ownership $ownership): bool
    {
        if ($ownership->user_id === $this->id) return false;
        $member = $ownership->members()->where('user_id', $this->id)->first();
        return $member && $member->role === 'viewer';
    }

    // ── Computed attributes ────────────────────────────────────────────────

    public function getInitialsAttribute(): string
    {
        $parts = explode(' ', trim($this->name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(mb_substr($part, 0, 1));
        }
        return $initials ?: 'U';
    }

    public function getIsKycVerifiedAttribute(): bool
    {
        return !is_null($this->kyc_verified_at);
    }

    public function getMemberSinceAttribute(): string
    {
        return $this->created_at->format('M Y');
    }
}
