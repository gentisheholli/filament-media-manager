<?php

namespace TomatoPHP\FilamentMediaManager\Resources\Actions;

use TomatoPHP\FilamentMediaManager\Models\Folder;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;

class CreateMediaAction
{
    public static function make(int $folder_id): Actions\Action
    {
        return Actions\Action::make('create_media')
            ->mountUsing(function () use ($folder_id){
                session()->put('folder_id', $folder_id);
            })
            ->label(trans('filament-media-manager::messages.media.actions.create.label'))
            ->icon('heroicon-o-plus')
            ->form([
                Forms\Components\FileUpload::make('file')
                    ->label(trans('filament-media-manager::messages.media.actions.create.form.file'))
                    ->maxSize('100000')
                    ->multiple()
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
                        foreach ($data['file'] as $file) {
                            $folder->model->addMedia($file)
                                ->multiple()
                                ->withCustomProperties([
                                    'description' => $data['description'],
                                    'title' => $data['title'],
                                ])
                                ->toMediaCollection($folder->collection);
                        }
                    }
                    else {
                        foreach ($data['file'] as $file) {
                            $folder->addMedia($file)
                                ->withCustomProperties([
                                    'description' => $data['description'],
                                    'title' => $data['title'],
                                ])
                                ->toMediaCollection($folder->collection);
                        }
                    }

                }

                Notification::make()->title(trans('filament-media-manager::messages.media.notifications.create-media'))->send();
            });
    }
}
