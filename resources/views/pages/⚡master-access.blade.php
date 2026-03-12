<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Usuaris i grups')] class extends Component
{
    use PasswordValidationRules;
    use ProfileValidationRules;

    public string $groupName = '';

    public string $userName = '';

    public string $userEmail = '';

    public string $userPassword = '';

    public string $userPasswordConfirmation = '';

    public string $userGroupId = '';

    public bool $userIsMaster = false;

    /** @var array<int, string> */
    public array $userGroupAssignments = [];

    /**
     * Initialize the component state.
     */
    public function mount(): void
    {
        $this->syncUserGroupAssignments();
    }

    /**
     * Get the existing groups.
     */
    #[Computed]
    public function groups(): Collection
    {
        return UserGroup::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get the existing users.
     */
    #[Computed]
    public function users(): Collection
    {
        return User::query()
            ->with('userGroup')
            ->orderByDesc('is_master')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new group.
     */
    public function createGroup(): void
    {
        Validator::make(
            ['group_name' => $this->groupName],
            ['group_name' => ['required', 'string', 'max:255', Rule::unique(UserGroup::class, 'name')]],
            [],
            ['group_name' => 'nom del grup'],
        )->validate();

        UserGroup::query()->create([
            'name' => $this->groupName,
        ]);

        $this->groupName = '';
        $this->dispatch('group-created');
    }

    /**
     * Create a new user.
     */
    public function createUser(): void
    {
        $validated = Validator::make(
            [
                'name' => $this->userName,
                'email' => $this->userEmail,
                'password' => $this->userPassword,
                'password_confirmation' => $this->userPasswordConfirmation,
                'user_group_id' => $this->userGroupId !== '' ? $this->userGroupId : null,
                'is_master' => $this->userIsMaster,
            ],
            [
                ...$this->profileRules(),
                'password' => $this->passwordRules(),
                'user_group_id' => [
                    Rule::requiredIf(! $this->userIsMaster),
                    'nullable',
                    'integer',
                    Rule::exists(UserGroup::class, 'id'),
                ],
                'is_master' => ['required', 'boolean'],
            ],
            [],
            [
                'name' => 'nom',
                'email' => 'correu electrònic',
                'password' => 'contrasenya',
                'user_group_id' => 'grup',
            ],
        )->validate();

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'user_group_id' => $validated['is_master'] ? null : (int) $validated['user_group_id'],
            'is_master' => $validated['is_master'],
        ]);

        $this->syncUserGroupAssignments();
        $this->resetUserForm();
        $this->dispatch('user-created');
    }

    /**
     * Persist a group change triggered from the user list.
     */
    public function updatedUserGroupAssignments(mixed $groupId, string $userId): void
    {
        $this->updateUserGroup((int) $userId, $groupId);
    }

    /**
     * Update the group assigned to an existing user.
     */
    public function updateUserGroup(int $userId, mixed $groupId): void
    {
        $validated = Validator::make(
            [
                'user_id' => $userId,
                'user_group_id' => $groupId !== '' ? $groupId : null,
            ],
            [
                'user_id' => ['required', 'integer', Rule::exists(User::class, 'id')],
                'user_group_id' => ['nullable', 'integer', Rule::exists(UserGroup::class, 'id')],
            ],
            [],
            [
                'user_group_id' => 'grup',
            ],
        )->validate();

        $user = User::query()->findOrFail($validated['user_id']);

        $user->update([
            'user_group_id' => $validated['user_group_id'],
        ]);

        $this->userGroupAssignments[$user->id] = $validated['user_group_id'] === null
            ? ''
            : (string) $validated['user_group_id'];

        $this->dispatch('user-group-updated');
    }

    /**
     * Reset the user form.
     */
    protected function resetUserForm(): void
    {
        $this->resetValidation();
        $this->userName = '';
        $this->userEmail = '';
        $this->userPassword = '';
        $this->userPasswordConfirmation = '';
        $this->userGroupId = '';
        $this->userIsMaster = false;
    }

    /**
     * Sync the select state shown in the user list.
     */
    protected function syncUserGroupAssignments(): void
    {
        $this->userGroupAssignments = User::query()
            ->orderByDesc('is_master')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $user): array => [
                $user->id => $user->user_group_id === null ? '' : (string) $user->user_group_id,
            ])
            ->all();
    }
};
?>

<section class="w-full">
    <div class="mx-auto flex w-full max-w-[90rem] flex-col gap-3 px-2.5 py-3 sm:gap-4 sm:px-4 sm:py-4 lg:px-5 xl:px-6">
        <div class="overflow-hidden rounded-xl border border-zinc-200/80 bg-linear-to-br from-stone-50 via-white to-amber-50 shadow-sm dark:border-zinc-700/70 dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-800 sm:rounded-2xl">
            <div class="flex flex-col gap-3 px-3 py-4 sm:px-4 sm:py-4 lg:gap-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl space-y-1">
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-zinc-500 dark:text-zinc-400">Control d'accés</p>
                        <flux:heading size="xl" level="1">{{ __('Usuaris i grups') }}</flux:heading>
                        <flux:subheading class="max-w-2xl text-sm">
                            {{ __('Crea grups, dona d’alta usuaris i defineix qui actua com a master dins de l’aplicació.') }}
                        </flux:subheading>
                    </div>

                    <div class="hidden items-center gap-2 xl:flex">
                        <x-action-message on="group-created">{{ __('Grup creat.') }}</x-action-message>
                        <x-action-message on="user-created">{{ __('Usuari creat.') }}</x-action-message>
                        <x-action-message on="user-group-updated">{{ __('Grup actualitzat.') }}</x-action-message>
                    </div>
                </div>

                <div class="grid gap-1.5 rounded-[1.25rem] border border-zinc-200/70 bg-white/80 p-2.5 backdrop-blur-sm dark:border-zinc-700/70 dark:bg-zinc-950/40 sm:grid-cols-3">
                    <div class="rounded-xl bg-zinc-50 px-2.5 py-2 dark:bg-zinc-900/80">
                        <p class="text-[0.7rem] font-medium uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ __('Grups') }}</p>
                        <p class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ $this->groups->count() }}</p>
                    </div>
                    <div class="rounded-xl bg-zinc-50 px-2.5 py-2 dark:bg-zinc-900/80">
                        <p class="text-[0.7rem] font-medium uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ __('Usuaris') }}</p>
                        <p class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ $this->users->count() }}</p>
                    </div>
                    <div class="rounded-xl bg-zinc-50 px-2.5 py-2 dark:bg-zinc-900/80">
                        <p class="text-[0.7rem] font-medium uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ __('Masters') }}</p>
                        <p class="text-xl font-semibold text-zinc-900 dark:text-zinc-50">{{ $this->users->where('is_master', true)->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-3 xl:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)]">
            <div class="space-y-3">
                <article class="rounded-xl border border-zinc-200/80 bg-white/90 p-3 shadow-sm dark:border-zinc-700/70 dark:bg-zinc-900/70 sm:rounded-2xl sm:p-4">
                    <flux:heading size="lg">{{ __('Nou grup') }}</flux:heading>
                    <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">
                        {{ __('Cada botiga es comparteix a nivell de grup, així que aquest és el primer pas per organitzar l’accés.') }}
                    </flux:text>

                    <form wire:submit="createGroup" class="mt-3 space-y-2.5">
                        <flux:field>
                            <flux:label>{{ __('Nom del grup') }}</flux:label>
                            <flux:input wire:model="groupName" :placeholder="__('Ex. Família Serra')" />
                            <flux:error name="group_name" />
                        </flux:field>

                        <flux:button variant="primary" type="submit">
                            {{ __('Crear grup') }}
                        </flux:button>
                    </form>
                </article>

                <article class="rounded-xl border border-zinc-200/80 bg-white/90 p-3 shadow-sm dark:border-zinc-700/70 dark:bg-zinc-900/70 sm:rounded-2xl sm:p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <flux:heading size="lg">{{ __('Grups existents') }}</flux:heading>
                            <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">
                                {{ __('Cada grup veu les botigues compartides i els productes públics dels seus membres.') }}
                            </flux:text>
                        </div>
                    </div>

                    <div class="mt-3 space-y-2">
                        @forelse ($this->groups as $group)
                            <div class="rounded-xl border border-zinc-200/70 bg-zinc-50/80 px-2.5 py-2 dark:border-zinc-700/70 dark:bg-zinc-950/40">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-zinc-50">{{ $group->name }}</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $group->users_count === 1
                                                ? __('1 usuari')
                                                : __(':count usuaris', ['count' => $group->users_count]) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-zinc-200 px-4 py-6 text-center dark:border-zinc-700">
                                <flux:text class="text-zinc-500 dark:text-zinc-400">
                                    {{ __('Encara no hi ha cap grup creat.') }}
                                </flux:text>
                            </div>
                        @endforelse
                    </div>
                </article>
            </div>

            <div class="space-y-3">
                <article class="rounded-xl border border-zinc-200/80 bg-white/90 p-3 shadow-sm dark:border-zinc-700/70 dark:bg-zinc-900/70 sm:rounded-2xl sm:p-4">
                    <flux:heading size="lg">{{ __('Nou usuari') }}</flux:heading>
                    <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">
                        {{ __('Els usuaris normals han d’estar dins d’un grup. Un master pot entrar al panell i gestionar nous accessos.') }}
                    </flux:text>

                    <form wire:submit="createUser" class="mt-3 grid gap-2.5 md:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Nom') }}</flux:label>
                            <flux:input wire:model="userName" />
                            <flux:error name="name" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Correu electrònic') }}</flux:label>
                            <flux:input wire:model="userEmail" type="email" />
                            <flux:error name="email" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Contrasenya') }}</flux:label>
                            <flux:input wire:model="userPassword" type="password" />
                            <flux:error name="password" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Confirma la contrasenya') }}</flux:label>
                            <flux:input wire:model="userPasswordConfirmation" type="password" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Grup') }}</flux:label>
                            <flux:select wire:model="userGroupId">
                                <option value="">{{ __('Selecciona un grup') }}</option>
                                @foreach ($this->groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="user_group_id" />
                        </flux:field>

                        <flux:field class="self-end">
                            <flux:label>{{ __('Permisos') }}</flux:label>
                            <label class="flex min-h-11 items-center gap-3 rounded-2xl border border-zinc-200/70 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                <input
                                    type="checkbox"
                                    wire:model="userIsMaster"
                                    class="size-4 rounded border-zinc-300 text-stone-700 focus:ring-stone-500 dark:border-zinc-600 dark:bg-zinc-900"
                                >
                                <span>{{ __('Usuari master') }}</span>
                            </label>
                        </flux:field>

                        <div class="md:col-span-2">
                            <flux:button variant="primary" type="submit">
                                {{ __('Crear usuari') }}
                            </flux:button>
                        </div>
                    </form>
                </article>

                <article class="rounded-xl border border-zinc-200/80 bg-white/90 p-3 shadow-sm dark:border-zinc-700/70 dark:bg-zinc-900/70 sm:rounded-2xl sm:p-4">
                    <flux:heading size="lg">{{ __('Usuaris existents') }}</flux:heading>
                    <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">
                        {{ __('Assigna un grup, canvia’l o deixa l’usuari sense grup directament des d’aquesta llista.') }}
                    </flux:text>

                    <div class="mt-3 space-y-2">
                        @foreach ($this->users as $user)
                            <div wire:key="user-{{ $user->id }}" class="rounded-xl border border-zinc-200/70 bg-zinc-50/80 px-2.5 py-2 dark:border-zinc-700/70 dark:bg-zinc-950/40">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-zinc-50">{{ $user->name }}</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $user->email }}</p>
                                    </div>

                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                                        @if ($user->is_master)
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-sm text-amber-800 dark:bg-amber-950/50 dark:text-amber-200">
                                                {{ __('Master') }}
                                            </span>
                                        @endif

                                        <div class="min-w-full sm:min-w-44">
                                            <flux:field>
                                                <flux:label class="sr-only">{{ __('Grup de l’usuari') }}</flux:label>
                                                <flux:select
                                                    wire:model="userGroupAssignments.{{ $user->id }}"
                                                >
                                                    <option value="">{{ __('Sense grup') }}</option>
                                                    @foreach ($this->groups as $group)
                                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                                    @endforeach
                                                </flux:select>
                                            </flux:field>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>
