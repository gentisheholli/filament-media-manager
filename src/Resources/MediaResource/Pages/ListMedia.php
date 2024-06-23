<?php

namespace TomatoPHP\FilamentMediaManager\Resources\MediaResource\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use TomatoPHP\FilamentIcons\Components\IconPicker;
use TomatoPHP\FilamentMediaManager\Models\Folder;
use TomatoPHP\FilamentMediaManager\Resources\MediaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMedia extends ManageRecords
{
    protected static string $resource = MediaResource::class;

    public ?int $folder_id = null;
    public ?Folder $folder = null;


    public function getTitle(): string|Htmlable
    {
        return $this->folder->name; // TODO: Change the autogenerated stub
    }

    public function mount(): void
    {
        parent::mount(); // TODO: Change the autogenerated stub


        if(!request()->has('folder_id')){
            abort(404, 'Folder ID is required');
        }

        $folder = Folder::find(request()->get('folder_id'));
        if(!$folder){
            abort(404, 'Folder ID is required');
        }
        else {
            if($folder->is_protected && !session()->has('folder_password')){
                abort(403, 'You Cannot Access This Folder');
            }
        }

        $this->folder = $folder;
        $this->folder_id = request()->get('folder_id');
        session()->put('folder_id', $this->folder_id);
    }

    protected function getHeaderActions(): array
    {
        $folder_id = $this->folder_id;
        $form = config('filament-media-manager.model.folder')::find($folder_id)?->toArray();
        return [
            Actions\Action::make('create_media')
                ->mountUsing(function () use ($folder_id){
                    session()->put('folder_id', $folder_id);
                })
                ->label(trans('filament-media-manager::messages.media.actions.create.label'))
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label(trans('filament-media-manager::messages.media.actions.create.form.file'))
                        ->maxSize('100000')
                        ->columnSpanFull()
                        ->required()
                        ->storeFiles(false),
                    Forms\Components\TextInput::make('title')
                        ->label(trans('filament-media-manager::messages.media.actions.create.form.title'))
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->label(trans('filament-media-manager::messages.media.actions.create.form.description'))
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) use ($folder_id) {
                    $folder = Folder::find($folder_id);
                    if($folder){
                        if($folder->model){
                            $folder->model->addMedia($data['file'])
                                ->withCustomProperties([
                                    'title' => $data['title'],
                                    'description' => $data['description']
                                ])
                                ->toMediaCollection($folder->collection);
                        }
                        else {
                            $folder->addMedia($data['file'])
                                ->withCustomProperties([
                                    'title' => $data['title'],
                                    'description' => $data['description']
                                ])
                                ->toMediaCollection($folder->collection);
                        }

                    }

                    Notification::make()->title(trans('filament-media-manager::messages.media.notificaitons.create-media'))->send();
                }),
            Actions\Action::make('delete_folder')
                ->mountUsing(function () use ($folder_id){
                    session()->put('folder_id', $folder_id);
                })
                ->requiresConfirmation()
                ->label(trans('filament-media-manager::messages.media.actions.delete.label'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function () use ($folder_id){
                    $folder = config('filament-media-manager.model.folder')::find($folder_id);
                    $folder->delete();
                    session()->forget('folder_id');

                    Notification::make()->title(trans('filament-media-manager::messages.media.notificaitons.delete-folder'))->send();
                    return redirect()->route('filament.'.filament()->getCurrentPanel()->getId().'.resources.folders.index');
                }),
            Actions\Action::make('edit_current_folder')
                ->mountUsing(function () use ($folder_id){
                    session()->put('folder_id', $folder_id);
                })
                ->label(trans('filament-media-manager::messages.media.actions.edit.label'))
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->form(function (){
                    return [
                       Grid::make([
                           "sm" => 1,
                           "md" => 2
                       ])
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(trans('filament-media-manager::messages.folders.columns.name'))
                                    ->columnSpanFull()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->label(trans('filament-media-manager::messages.folders.columns.description'))
                                    ->columnSpanFull()
                                    ->maxLength(255),
                                IconPicker::make('icon')
                                    ->label(trans('filament-media-manager::messages.folders.columns.icon')),
                                Forms\Components\ColorPicker::make('color')
                                    ->label(trans('filament-media-manager::messages.folders.columns.color')),
                                Forms\Components\Toggle::make('is_protected')
                                    ->label(trans('filament-media-manager::messages.folders.columns.is_protected'))
                                    ->live()
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('password')
                                    ->label(trans('filament-media-manager::messages.folders.columns.password'))
                                    ->hidden(fn(Forms\Get $get) => !$get('is_protected'))
                                    ->confirmed()
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label(trans('filament-media-manager::messages.folders.columns.password_confirmation'))
                                    ->hidden(fn(Forms\Get $get) => !$get('is_protected'))
                                    ->password()
                                    ->required()
                                    ->revealable()
                                    ->maxLength(255),
                            ])
                    ];
                })
                ->fillForm($form)
                ->action(function (array $data) use ($folder_id){
                    $folder = config('filament-media-manager.model.folder')::find($folder_id);
                    $folder->update($data);

                    Notification::make()->title(trans('filament-media-manager::messages.media.notificaitons.edit-folder'))->send();
                })
        ];
    }
}
