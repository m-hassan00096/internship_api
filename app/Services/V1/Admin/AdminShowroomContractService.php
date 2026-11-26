<?php

namespace App\Services\V1\Admin;

use App\Enums\ContractStatus;
use App\Models\Showroom;
use App\Models\ShowroomContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

class AdminShowroomContractService
{
    /**
     * Get showroom with related data for commercial & contract details view.
     */
    public function getContractDetails(int|string $id): Showroom
    {
        return Showroom::withCount(['rooms', 'packages'])
            ->with([
                'user',
                'activeContract',
                'activeVerification',
            ])
            ->findOrFail($id);
    }

    /**
     * Get historical and scheduled contracts for a showroom.
     *
     * @return Collection<int, ShowroomContract>
     */
    public function getContractHistory(int|string $id): Collection
    {
        $showroom = Showroom::findOrFail($id);

        return $showroom->contracts()
            ->with(['showroom.activeContract', 'showroom.activeVerification'])
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Renew and schedule a new contract for a showroom.
     *
     * @param  array<string, mixed>  $data
     */
    public function renewContract(
        int|string $id,
        array $data,
        ?UploadedFile $contractFile,
        User $adminUser
    ): ShowroomContract {
        $showroom = Showroom::findOrFail($id);

        $contractFilePath = $contractFile ? $contractFile->store('contracts', 'public') : null;
        $contractNumber = ! empty($data['contractNumber'])
            ? $data['contractNumber']
            : (! empty($data['contract_number'])
                ? $data['contract_number']
                : $this->generateContractNumber());

        return $showroom->contracts()->create([
            'contract_number' => $contractNumber,
            'started_at' => $data['startedAt'] ?? $data['started_at'],
            'end_at' => $data['endAt'] ?? $data['end_at'],
            'amount' => $data['amount'],
            'max_rooms' => $data['maxRooms'] ?? $data['max_rooms'],
            'max_packages' => $data['maxPackages'] ?? $data['max_packages'],
            'contract_file' => $contractFilePath,
            'created_by' => $adminUser->id,
            'status' => ContractStatus::ACTIVE->value,
        ]);
    }

    /**
     * Generate unique sequential contract number in format CNT-YYYY-0001.
     */
    public function generateContractNumber(): string
    {
        $year = date('Y');
        $count = ShowroomContract::whereYear('created_at', $year)->count() + 1;
        do {
            $number = sprintf('CNT-%s-%04d', $year, $count++);
        } while (ShowroomContract::where('contract_number', $number)->exists());

        return $number;
    }
}
