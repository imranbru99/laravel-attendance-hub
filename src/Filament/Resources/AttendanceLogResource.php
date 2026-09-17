<?php

namespace ImranDevBd\AttendanceHub\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use ImranDevBd\AttendanceHub\Enums\PunchType;
use ImranDevBd\AttendanceHub\Enums\VerifyMode;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;

class AttendanceLogResource extends Resource
{
    protected static ?string $model = AttendanceLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $modelLabel = 'Attendance Log';
    protected static ?string $pluralModelLabel = 'Attendance Logs';

    public static function getNavigationGroup(): ?string
    {
        return config('attendance-hub.filament.navigation_group', 'Attendance Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('device_user_id')
                ->label('Device PIN / Badge ID')
                ->disabled(),

            Forms\Components\TextInput::make('employee_id')
                ->label('Mapped Employee ID')
                ->disabled(),

            Forms\Components\DateTimePicker::make('punched_at')
                ->label('Punch Time')
                ->disabled(),

            Forms\Components\TextInput::make('verify_mode')
                ->label('Verification Mode')
                ->disabled(),

            Forms\Components\TextInput::make('punch_type')
                ->label('Punch Type')
                ->disabled(),

            Forms\Components\KeyValue::make('location')
                ->label('GPS / Geofence Data')
                ->disabled(),

            Forms\Components\KeyValue::make('raw_payload')
                ->label('Raw Audit Payload')
                ->columnSpanFull()
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_id')
                    ->label('Employee')
                    ->searchable()
                    ->default(fn ($record) => "PIN: {$record->device_user_id}")
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('device.name')
                    ->label('Source Terminal')
                    ->default('Virtual / API')
                    ->searchable(),

                Tables\Columns\TextColumn::make('punched_at')
                    ->label('Punch Time')
                    ->dateTime('M d, Y h:i:s A')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('verify_mode')
                    ->label('Method')
                    ->colors([
                        'primary' => 'fingerprint',
                        'success' => 'face',
                        'warning' => 'card',
                        'info' => 'gps',
                        'gray' => 'other',
                    ]),

                Tables\Columns\BadgeColumn::make('punch_type')
                    ->label('State')
                    ->colors([
                        'success' => 'check_in',
                        'danger' => 'check_out',
                        'warning' => 'break_out',
                        'info' => 'break_in',
                    ]),

                Tables\Columns\TextColumn::make('punch_hash')
                    ->label('Hash')
                    ->limit(12)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('punched_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('device_id')
                    ->label('Device')
                    ->relationship('device', 'name'),

                Tables\Filters\SelectFilter::make('verify_mode')
                    ->options(collect(VerifyMode::cases())->mapWithKeys(fn ($m) => [$m->value => ucfirst($m->value)])->toArray()),

                Tables\Filters\SelectFilter::make('punch_type')
                    ->options(collect(PunchType::cases())->mapWithKeys(fn ($p) => [$p->value => ucfirst($p->value)])->toArray()),

                Tables\Filters\Filter::make('punched_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From Date'),
                        Forms\Components\DatePicker::make('until')->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('punched_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('punched_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }
}
