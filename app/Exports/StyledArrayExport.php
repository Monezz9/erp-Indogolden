<?php

namespace App\Exports;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StyledArrayExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    /**
     * @param  array<int, string>  $columns
     * @param  array<string, string>  $labels
     */
    public function __construct(
        protected Collection $rows,
        protected array $columns,
        protected array $labels = [],
    ) {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return array_map(function (string $column): string {
            if (isset($this->labels[$column])) {
                return $this->labels[$column];
            }

            return (string) str($column)
                ->replace('_', ' ')
                ->title();
        }, $this->columns);
    }

    /**
     * @param  mixed  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        $source = is_array($row) ? $row : (method_exists($row, 'toArray') ? $row->toArray() : []);

        return array_map(function (string $column) use ($source) {
            $value = Arr::get($source, $column);

            if (is_object($value) && method_exists($value, 'value')) {
                $value = $value->value;
            }

            if (is_string($value) && preg_match('/^[=+\-@]/', $value)) {
                return "'".$value;
            }

            return $value;
        }, $this->columns);
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $highestRow = $event->sheet->getHighestRow();
                $highestColumn = $event->sheet->getHighestColumn();
                $range = 'A1:'.$highestColumn.$highestRow;

                $event->sheet->getStyle('A1:'.$highestColumn.'1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'B91C1C'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $event->sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB'],
                        ],
                    ],
                ]);

                $event->sheet->freezePane('A2');
                $event->sheet->setAutoFilter('A1:'.$highestColumn.'1');
            },
        ];
    }
}
