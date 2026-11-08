<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AuditRepository;
use App\Repositories\DeviceRepository;
use PDOException;

final class DeviceController
{
    public function __construct(
        private readonly DeviceRepository $devices,
        private readonly ?AuditRepository $audit = null
    )
    {
    }

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        \render('devices/index', [
            'devices' => $this->devices->search(
                $this->optionalString($_GET['search'] ?? null),
                $this->optionalString($_GET['status'] ?? null),
                $this->optionalString($_GET['type'] ?? null),
                $page
            ),
            'filters' => [
                'search' => (string) ($_GET['search'] ?? ''),
                'status' => (string) ($_GET['status'] ?? ''),
                'type' => (string) ($_GET['type'] ?? ''),
            ],
            'types' => DeviceRepository::TYPES,
        ]);
    }

    public function showForm(?array $device = null, array $errors = []): void
    {
        \render('devices/form', [
            'device' => $device ?? [
                'name' => '', 'hostname' => '', 'ip_address' => '',
                'mac_address' => '', 'device_type' => 'other',
                'description' => '', 'monitoring_enabled' => 1,
            ],
            'errors' => $errors,
            'types' => DeviceRepository::TYPES,
            'editing' => $device !== null,
        ]);
    }

    public function save(array $input, ?int $id, ?int $createdBy): ?array
    {
        $device = [
            'name' => trim((string) ($input['name'] ?? '')),
            'hostname' => trim((string) ($input['hostname'] ?? '')),
            'ip_address' => trim((string) ($input['ip_address'] ?? '')),
            'mac_address' => trim((string) ($input['mac_address'] ?? '')),
            'device_type' => (string) ($input['device_type'] ?? ''),
            'description' => trim((string) ($input['description'] ?? '')),
            'monitoring_enabled' => isset($input['monitoring_enabled']) ? 1 : 0,
        ];
        $errors = $this->validate($device);

        if ($errors !== []) {
            return ['device' => $device, 'errors' => $errors];
        }

        try {
            if ($id === null) {
                $id = $this->devices->create($device, $createdBy);
                $this->audit?->record($createdBy, 'create', 'device', $id, ['name' => $device['name'], 'ip_address' => $device['ip_address']]);
            } else {
                $this->devices->update($id, $device);
                $this->audit?->record($createdBy, 'update', 'device', $id, ['name' => $device['name'], 'ip_address' => $device['ip_address']]);
            }
        } catch (PDOException $exception) {
            if ((int) $exception->errorInfo[1] === 1062) {
                return ['device' => $device, 'errors' => ['ip_address' => 'That IP address is already registered.']];
            }
            throw $exception;
        }

        return null;
    }

    private function validate(array $device): array
    {
        $errors = [];
        if ($device['name'] === '' || strlen($device['name']) > 120) {
            $errors['name'] = 'Name is required and must be 120 characters or fewer.';
        }
        if (!filter_var($device['ip_address'], FILTER_VALIDATE_IP)) {
            $errors['ip_address'] = 'Enter a valid IPv4 or IPv6 address.';
        }
        if ($device['mac_address'] !== '' && !preg_match('/^[0-9A-Fa-f]{2}([:-][0-9A-Fa-f]{2}){5}$/', $device['mac_address'])) {
            $errors['mac_address'] = 'Enter a valid MAC address.';
        }
        if (!in_array($device['device_type'], DeviceRepository::TYPES, true)) {
            $errors['device_type'] = 'Select a supported device type.';
        }
        return $errors;
    }

    private function optionalString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';
        return $value === '' ? null : $value;
    }
}
