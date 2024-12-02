<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientsExport implements FromCollection, WithHeadings
{
    protected $status;

    public function __construct($status)
    {
        $this->status = $status;
    }

    /**
     * Retorna os dados filtrados para exportação.
     */
    public function collection()
    {
        return Client::where('status', $this->status)
            ->select('name', 'gender', 'address', 'email', 'whatsapp', 'status')
            ->get();
    }

    /**
     * Define os cabeçalhos do Excel.
     */
    public function headings(): array
    {
        return ['Name', 'Gender', 'Address', 'Email', 'Whatsapp', 'Status'];
    }
}
