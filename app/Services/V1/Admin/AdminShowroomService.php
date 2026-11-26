<?php

namespace App\Services\V1\Admin;

use App\Enums\ContractStatus;
use App\Enums\ShowroomStatus;
use App\Models\Showroom;
use App\Models\ShowroomContract;
use App\Models\User;
use App\Models\VendorRegistration;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminShowroomService
{
    public function __construct(
        private AdminShowroomContractService $contractService
    ) {}

    /**
     * Get paginated showrooms with KPI statistics for Admin Dashboard.
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *     items: LengthAwarePaginator,
     *     kpis: array{
     *         totalShowrooms: int,
     *         activeContracts: int,
     *         needingRenewal: int,
     *         suspendedOrInactive: int
     *     }
     * }
     */
    public function getShowrooms(array $filters = [], int $perPage = 15): array
    {
        $query = Showroom::query()
            ->with(['businessType', 'user', 'activeContract', 'activeVerification'])
            ->withCount(['rooms', 'packages']);

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }

        if (! empty($filters['businessTypeId']) || ! empty($filters['business_type_id'])) {
            $query->where('business_type_id', (int) ($filters['businessTypeId'] ?? $filters['business_type_id']));
        }

        $contractStatusFilter = $filters['contractStatus'] ?? $filters['contract_status'] ?? null;
        if ($contractStatusFilter !== null && $contractStatusFilter !== '') {
            $contractStatus = (int) $contractStatusFilter;
            $query->whereHas('contracts', function (Builder $contractQuery) use ($contractStatus): void {
                $contractQuery->where('status', $contractStatus);
            });
        }

        $items = $query->latest('id')->paginate($perPage);

        $kpis = [
            'totalShowrooms' => Showroom::count(),
            'activeContracts' => ShowroomContract::where('status', ContractStatus::ACTIVE->value)
                ->whereDate('started_at', '<=', now())
                ->whereDate('end_at', '>=', now())
                ->distinct('showrooms_id')
                ->count('showrooms_id'),
            'needingRenewal' => ShowroomContract::where('status', ContractStatus::ACTIVE->value)
                ->whereDate('end_at', '>=', now())
                ->whereDate('end_at', '<=', now()->addDays(30))
                ->distinct('showrooms_id')
                ->count('showrooms_id'),
            'suspendedOrInactive' => Showroom::whereIn('status', [
                ShowroomStatus::SUSPENDED->value,
                ShowroomStatus::TERMINATED->value,
                ShowroomStatus::DRAFT->value,
            ])->count(),
        ];

        return [
            'items' => $items,
            'kpis' => $kpis,
        ];
    }

    /**
     * Get detailed showroom record with all relations and stats.
     */
    public function getShowroomDetails(int|string $id): Showroom
    {
        return Showroom::with([
            'businessType',
            'user',
            'activeContract',
            'activeVerification',
        ])
            ->withCount(['rooms', 'packages'])
            ->findOrFail($id);
    }

    /**
     * Create a complete Showroom profile along with main branch and contract (Wizard).
     *
     * @param  array<string, mixed>  $data
     */
    public function createShowroom(
        array $data,
        ?UploadedFile $logo,
        ?UploadedFile $coverImage,
        ?UploadedFile $contractFile,
        User $adminUser
    ): Showroom {
        return DB::transaction(function () use ($data, $logo, $coverImage, $contractFile, $adminUser): Showroom {
            $registrationId = $data['vendorRegistrationId'] ?? $data['vendor_registration_id'] ?? null;
            $registration = VendorRegistration::findOrFail($registrationId);

            if (! $registration->user_id) {
                throw ValidationException::withMessages([
                    'vendorRegistrationId' => [__('admin/showroom.errors.registration_not_approved')],
                ]);
            }

            if (Showroom::where('user_id', $registration->user_id)->exists()) {
                throw ValidationException::withMessages([
                    'vendorRegistrationId' => [__('admin/showroom.errors.user_has_showroom')],
                ]);
            }

            $logoPath = $logo ? $logo->store('showrooms/logos', 'public') : null;
            $coverPath = $coverImage ? $coverImage->store('showrooms/covers', 'public') : null;

            $name = ! empty($data['name']) ? $data['name'] : $registration->vendor_name;
            $slug = ! empty($data['slug'])
                ? Str::slug($data['slug'])
                : Str::slug($name).'-'.Str::lower(Str::random(5));

            $showroom = Showroom::create([
                'user_id' => $registration->user_id,
                'business_type_id' => $registration->business_type_id,
                'name' => $name,
                'slug' => $slug,
                'bio' => $data['bio'] ?? null,
                'logo' => $logoPath,
                'cover_image' => $coverPath,
                'phone' => $registration->phone,
                'whatsapp' => $registration->has_whatsapp ? $registration->phone : null,
                'status' => $data['status'] ?? ShowroomStatus::ACTIVE->value,
            ]);

            // Create Main Branch if provided
            if (! empty($data['branch']['name'])) {
                $branchData = $data['branch'];
                $showroom->branches()->create([
                    'name' => $branchData['name'],
                    'city' => $branchData['city'] ?? '',
                    'address' => $branchData['address'] ?? '',
                    'phone' => $branchData['phone'] ?? $registration->phone,
                    'whatsapp' => $branchData['whatsapp'] ?? ($registration->has_whatsapp ? $registration->phone : null),
                    'is_main' => true,
                    'is_active' => true,
                ]);
            }

            // Create Contract
            $contractData = $data['contract'];
            $contractFilePath = $contractFile ? $contractFile->store('contracts', 'public') : null;
            $contractNumber = ! empty($contractData['contractNumber'])
                ? $contractData['contractNumber']
                : (! empty($contractData['contract_number'])
                    ? $contractData['contract_number']
                    : $this->contractService->generateContractNumber());

            $showroom->contracts()->create([
                'contract_number' => $contractNumber,
                'started_at' => $contractData['startedAt'] ?? $contractData['started_at'],
                'end_at' => $contractData['endAt'] ?? $contractData['end_at'],
                'amount' => $contractData['amount'],
                'max_rooms' => $contractData['maxRooms'] ?? $contractData['max_rooms'],
                'max_packages' => $contractData['maxPackages'] ?? $contractData['max_packages'],
                'contract_file' => $contractFilePath,
                'created_by' => $adminUser->id,
                'status' => ContractStatus::ACTIVE->value,
            ]);

            return $showroom->fresh([
                'businessType',
                'user',
                'branches',
                'activeContract',
                'contracts',
            ]);
        });
    }

    public function updateStatus(int|string $id, int|ShowroomStatus $status): Showroom
    {
        $showroom = Showroom::findOrFail($id);

        $statusValue = $status instanceof ShowroomStatus ? $status->value : $status;
        $showroom->update(['status' => $statusValue]);

        return $showroom;
    }
}
