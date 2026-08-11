<?php

namespace App\Helpers {

    use App\Models\User;

    class CompanyHelper
    {
        public static function currentCompanyId(): ?int
        {
            $user = auth()->user();

            if (!$user) {
                return null;
            }

            if ($user->role === User::ROLE_SUPER_ADMIN) {
                return session('current_company_id') ?: null;
            }

            return $user->company_id;
        }

        public static function currentCompanyName(): string
        {
            $companyId = self::currentCompanyId();
            if (!$companyId) {
                return 'All Companies';
            }

            return \App\Models\Company::where('id', $companyId)->value('name') ?? 'All Companies';
        }
    }
}

namespace {
    if (!function_exists('current_company_id')) {
        function current_company_id(): ?int
        {
            return \App\Helpers\CompanyHelper::currentCompanyId();
        }
    }
}
