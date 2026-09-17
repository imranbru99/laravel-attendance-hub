<?php

namespace ImranDevBd\AttendanceHub\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;

class AttendanceDeviceResource extends Resource
{
    protected static ?string $model = AttendanceDevice::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $modelLabel = 'Attendance Device';
    protected static ?string $pluralModelLabel = 'Attendance Devices';

    public static function getNavigationGroup(): ?string
    {
        return config('attendance-hub.filament.navigation_group', 'Attendance Management');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Device Identity')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Main Entrance Gate ZK1'),

                Forms\Components\Select::make('provider')
                    ->required()
                    ->options([
                        'zkteco' => 'ZKTeco (Native TCP/UDP :4370)',
                        'adms' => 'ADMS / iClock Push (eSSL / Anviz / Realtime / ZK-Cloud)',
                        'hikvision' => 'Hikvision (ISAPI HTTP/REST)',
                        'suprema' => 'Suprema (BioStar 2 REST API)',
                        'dahua' => 'Dahua (HTTP/CGI SDK)',
                        'virtual' => 'Virtual (Mobile / GPS / QR / Kiosk)',
                    ])
                    ->default('zkteco')
                    ->reactive(),

                Forms\Components\TextInput::make('model')
                    ->default('auto')
                    ->placeholder('e.g. K40, FaceStation 2, DS-K1T671, or auto'),

                Forms\Components\TextInput::make('serial_number')
                    ->label('Serial Number')
                    ->placeholder('e.g. BKT72109823 (Required for ADMS push)'),

                Forms\Components\TextInput::make('branch_id')
                    ->label('Branch ID / Location')
                    ->numeric(),
            ])->columns(2),

            Forms\Components\Section::make('Network & Connection')->schema([
                Forms\Components\TextInput::make('ip')
                    ->label('IP Address / Host')
                    ->placeholder('192.168.1.201'),

                Forms\Components\TextInput::make('port')
                    ->numeric()
                    ->default(4370),

                Forms\Components\Select::make('timezone')
                    ->options([
                        'UTC' => 'UTC',
                        'Asia/Dhaka' => 'Asia/Dhaka (+06:00)',
                        'Asia/Kolkata' => 'Asia/Kolkata (+05:30)',
                        'Asia/Dubai' => 'Asia/Dubai (+04:00)',
                        'America/New_York' => 'America/New_York (-05:00)',
                        'Europe/London' => 'Europe/London (+00:00)',
                    ])
                    ->default('UTC')
                    ->searchable(),

                Forms\Components\Toggle::make('auto_clear_logs')
                    ->label('Auto Clear Device Buffer')
                    ->helperText('Clear logs from device buffer after successful sync to prevent memory overflow.')
                    ->default(false),

                Forms\Components\KeyValue::make('connection_settings')
                    ->label('Additional Connection Parameters (Username, Password, Token, etc.)')
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'online',
                        'danger' => 'offline',
                        'warning' => 'warning',
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'online',
                        'heroicon-m-x-circle' => 'offline',
                        'heroicon-m-exclamation-triangle' => 'warning',
                    ]),

                Tables\Columns\BadgeColumn::make('provider')
                    ->colors([
                        'primary' => 'zkteco',
                        'success' => 'adms',
                        'warning' => 'hikvision',
                        'danger' => 'suprema',
                        'info' => 'virtual',
                    ]),

                Tables\Columns\TextColumn::make('ip')
                    ->label('Address')
                    ->formatStateUsing(fn ($record) => $record->ip ? "{$record->ip}:{$record->port}" : 'Cloud / WAN Push'),

                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Serial')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Last Seen')
                    ->since()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_synced_at')
                    ->label('Last Synced')
                    ->since()
                    ->toggleable(),
            ])
            ->actions([
                Tables\Actions\Action::make('syncNow')
                    ->label('Sync Now')
                    ->icon('heroicon-m-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (AttendanceDevice $record) {
                        try {
                            $logs = AttendanceHub::sync($record);
                            Notification::make()
                                ->title('Sync Successful')
                                ->body("Fetched and processed {$logs->count()} attendance logs.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Sync Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('ping')
                    ->label('Ping')
                    ->icon('heroicon-m-signal')
                    ->color('gray')
                    ->action(function (AttendanceDevice $record) {
                        try {
                            $driver = AttendanceHub::device($record);
                            $online = $driver->ping();
                            $driver->disconnect();

                            if ($online) {
                                $record->markOnline();
                                Notification::make()->title('Device Online')->success()->send();
                            } else {
                                $record->markOffline();
                                Notification::make()->title('Device Offline')->danger()->send();
                            }
                        } catch (\Throwable $e) {
                            $record->markOffline();
                            Notification::make()->title('Ping Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
