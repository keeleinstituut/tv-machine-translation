<?php

namespace App\Policies;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class SettingPolicy
{
    public function view(mixed $user): bool
    {
        return Auth::hasPrivilege("EDIT_MACHINE_TRANSLATION_SETTINGS");
    }

    public function update(mixed $user): bool
    {
        return Auth::hasPrivilege("EDIT_MACHINE_TRANSLATION_SETTINGS");
    }

    // Should serve as an query enhancement to Eloquent queries
    // to filter out objects that the user does not have permissions to.
    //
    // Example usage in query:
    // Role::getModel()->withGlobalScope('policy', RolePolicy::scope())->get();
    //
    // The 'policy' string in the example is not strict and is used internally to identify
    // the scope applied in Eloquent querybuilder. It can be something else as well,
    // but it should correspond with the intentions of the scope. Using 'policy' provides
    // general understanding throughout the whole project that the applied scope is related to policy.
    // The withGlobalScope method does not apply the scope globally, it applies to only the querybuilder
    // of current query. The method name could be different, but in the sake of reusability
    // we can use this method that's provided by Laravel and used internally.
    //
    public static function scope() {
        return new Scope\SettingScope();
    }
}

// Scope resides in the same file with Policy to enforce scope creation with policy creation.
namespace App\Policies\Scope;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope as IScope;

class SettingScope implements IScope {
    /**
    * Apply the scope to a given Eloquent query builder.
    */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('institution_id', Auth::user()->institutionId);
    }
}
