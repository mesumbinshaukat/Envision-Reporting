<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property-read \App\Models\OfficeSchedule|null $officeSchedule
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OfficeClosure> $officeClosures
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo_path',
        'office_latitude',
        'office_longitude',
        'office_radius_meters',
        'enforce_office_location',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'enforce_office_location' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function officeSchedule()
    {
        return $this->hasOne(OfficeSchedule::class);
    }

    public function officeClosures()
    {
        return $this->hasMany(OfficeClosure::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function bonuses()
    {
        return $this->hasMany(Bonus::class);
    }

    public function salaryReleases()
    {
        return $this->hasMany(SalaryRelease::class);
    }

    public function employeeUsers()
    {
        return $this->hasMany(EmployeeUser::class, 'admin_id');
    }

    public function processedAttendanceFixRequests()
    {
        return $this->hasMany(AttendanceFixRequest::class, 'processed_by');
    }

    public function currencies()
    {
        return $this->hasMany(Currency::class);
    }

    public function baseCurrency()
    {
        return $this->hasOne(Currency::class)->where('is_base', true)->where('is_active', true);
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo_path) {
            return null;
        }

        $relativePath = ltrim($this->profile_photo_path, '/');

        return asset('storage/' . $relativePath);
    }

    // Helper method to check if this is an admin user
    public function isAdmin()
    {
        return true;
    }

    public function isEmployee()
    {
        return false;
    }
}
