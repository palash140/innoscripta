<?php

namespace App\Contracts;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface NewsProviderInterface
{
    public function fetchNews(
        int $page = 1,
        int $perPage = 10,
        Carbon $from = null,
        Carbon $to = null
    ): Collection;
    public function getProviderName(): string;
}
