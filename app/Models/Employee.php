<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\EmployeeIpWhitelist;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EmployeeIpWhitelist> $ipWhitelists
 */
class Employee extends Model
{
    use SoftDeletes;

    public const GEO_MODE_DISABLED = 'disabled';
    public const GEO_MODE_REQUIRED = 'required';
    public const GEO_MODE_REQUIRED_WITH_WHITELIST = 'required_with_whitelist';

    public const GEOLOCATION_MODE_OPTIONS = [
        self::GEO_MODE_DISABLED,
        self::GEO_MODE_REQUIRED,
        self::GEO_MODE_REQUIRED_WITH_WHITELIST,
    ];

    protected $fillable = [
        'user_id',
        'currency_id',
        'name',
        'marital_status',
        'primary_contact',
        'email',
        'role',
        'secondary_contact',
        'employment_type',
        'joining_date',
        'last_date',
        'salary',
        'commission_rate',
        'geolocation_required',
        'geolocation_mode',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'last_date' => 'date',
        'geolocation_required' => 'boolean',
        'geolocation_mode' => 'string',
    ];

    protected static function booted(): void
    {
        static::saving(function (Employee $employee) {
            if (!in_array($employee->geolocation_mode, self::GEOLOCATION_MODE_OPTIONS, true)) {
                $employee->geolocation_mode = $employee->geolocation_required ? self::GEO_MODE_REQUIRED : self::GEO_MODE_DISABLED;
            }

            $employee->geolocation_required = $employee->geolocation_mode !== self::GEO_MODE_DISABLED;
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function bonuses()
    {
        return $this->hasMany(Bonus::class);
    }

    public function salaryReleases()
    {
        return $this->hasMany(SalaryRelease::class);
    }

    public function employeeUser()
    {
        return $this->hasOne(EmployeeUser::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function hasUserAccount()
    {
        return $this->employeeUser()->exists();
    }

    public function ipWhitelists()
    {
        return $this->hasMany(EmployeeIpWhitelist::class);
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->relationLoaded('employeeUser') && $this->employeeUser) {
            return $this->employeeUser->profile_photo_url;
        }

        return optional($this->employeeUser)->profile_photo_url;
    }

    public function hasIpWhitelist(): bool
    {
        if ($this->relationLoaded('ipWhitelists')) {
            return $this->ipWhitelists->isNotEmpty();
        }

        return $this->ipWhitelists()->exists();
    }

    public function isIpWhitelisted(?string $ipv4, ?string $ipv6): bool
    {
        $whitelists = $this->relationLoaded('ipWhitelists')
            ? $this->ipWhitelists
            : $this->ipWhitelists()->get();

        if ($whitelists->isEmpty()) {
            return false;
        }

        $normalizedIpv4 = $ipv4 ? trim($ipv4) : null;
        $normalizedIpv6 = $ipv6 ? strtolower(trim($ipv6)) : null;

        return $whitelists->contains(function ($whitelist) use ($normalizedIpv4, $normalizedIpv6) {
            if ($whitelist->ip_version === 'ipv4' && $normalizedIpv4) {
                return $whitelist->ip_address === $normalizedIpv4;
            }

            if ($whitelist->ip_version === 'ipv6' && $normalizedIpv6) {
                return strtolower($whitelist->ip_address) === $normalizedIpv6;
            }

            return false;
        });
    }

    public function requiresGeolocation(): bool
    {
        return $this->geolocation_mode !== self::GEO_MODE_DISABLED;
    }

    public function enforcesOfficeRadius(): bool
    {
        return $this->geolocation_mode === self::GEO_MODE_REQUIRED;
    }

    public function usesWhitelistOverride(): bool
    {
        return $this->geolocation_mode === self::GEO_MODE_REQUIRED_WITH_WHITELIST;
    }

    public function geolocationModeLabel(): string
    {
        return match ($this->geolocation_mode) {
            self::GEO_MODE_DISABLED => 'Geolocation disabled',
            self::GEO_MODE_REQUIRED_WITH_WHITELIST => 'Geolocation via IP whitelist',
            default => 'Geolocation required (office radius)',
        };
    }
}
