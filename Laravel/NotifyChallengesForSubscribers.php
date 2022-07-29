<?php

namespace App\Console\Commands\Sales\Pushes;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Sr\Models\Notifications\UserNotificationQueue;
use Sr\Models\Notifications\UserNotificationTemplate;
use Sr\Models\SmartLog\UserActionsLog;
use Sr\Models\User;

/**
 * php artisan sales:notify-challenges-for-subscribers --notSend
 *
 * Принцип работы данной группы ПУШ-уведомлений основан на шаблоне пуш-уведомления,
 * а точнее на алиасе шаблона
 *
 * 'regular-challenges-subscribers-20220325' - формат алиаса шаблона
 * 'regular-challenges-subscribers' - стандартная строка для выборки группы шаблонов для подписчиков
 * '20220325' - дата в формате 'Ymd' когда надо отправить именно этот шаблон
 */
class NotifyChallengesForSubscribers extends Command
{
    /**
     * @var string
     */
    protected $signature = 'sales:notify-challenges-for-subscribers
        {--template= : Specify template for push notification}
        {--notSend : Dont really send notification}
    ';

    /**
     * @var string
     */
    protected $description = 'Send push notifications about challenges for subscribers';

    private $users;

    private $template;

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return mixed
     */
    public function handle()
    {
        Log::channel('debugging')->info(class_basename(__CLASS__) . ' command started');

        $isNeedSend = ! $this->option('notSend');

        $this->getTemplate($this->option('template'));

        if (! $this->template) return 1;

        Log::channel('debugging')->info('Шаблон сообщения: ' . $this->template->title_for_admin);

        $this->getUsersIds();
        Log::channel('debugging')->info(class_basename(__CLASS__) . ': Выбрано всего пользователей: ' . count($this->users));

        if ($isNeedSend) {
            $this->notifyUsers();
        }

        Log::channel('debugging')->info(class_basename(__CLASS__) . ' command finished.');

        return 0;
    }

    private function getTemplate(?string $templateAlias)
    {
        $today = today()->format('Ymd');

        $alias = $templateAlias ?? 'regular-challenges-subscribers-' . $today;

        $this->template = UserNotificationTemplate::where('alias', $alias)->first();

        if (! $this->template) {
            Log::channel('debugging')->error(class_basename(__CLASS__) . ': User notification template not found for the alias: ' . $alias);
        }
    }

    private function getUsersIds(): void
    {
        $usersHasSubscriptions = User::query()
            ->whereHas('subscriptions', fn($q) => $q
                ->notTest()
                ->active()
                ->whereNull('interrupted_at')
            )
            ->where('is_test', 0)
            ->latest('id')
            ->get(['id']);

        $this->users = UserActionsLog::query()
            ->distinct('user_id')
            ->select('user_id')
            ->whereIntegerInRaw('user_id', $usersHasSubscriptions->pluck('id')->toArray())
            ->where('created_at', '>', now()->subMonths(18))
            ->where('channel', 'ios')
            ->where(fn($q) => $q
                ->where('app_version', 'like', '4.%')
                ->orWhere('app_version', 'like', '3.8.%')
                ->orWhere('app_version', 'like', '3.7.%')
            )
            ->get(['user_id'])
            ->pluck('user_id')
            ->toArray();
    }

    private function notifyUsers()
    {
        if (empty($this->users)) {
            Log::channel('debugging')->warning(class_basename(__CLASS__) . ': Нет пользователей для оповещения.');
            return;
        }

        $sendAt = Carbon::now()->toDateTimeString();

        try {
            collect($this->users)
                ->map(fn ($userId) => [
                    'user_id' => $userId,
                    'user_notification_template_id' => $this->template->id,
                    'send_at' => $sendAt,
                    'status' => UserNotificationQueue::STATUS_PENDING
                ])
                ->chunk(1000)
                ->each(fn (Collection $chunk) =>
                    UserNotificationQueue::upsert($chunk->toArray(), ['user_id', 'user_notification_template_id'])
                );
        } catch (Exception $ex) {
            Log::channel('debugging')
                ->error(class_basename(__CLASS__) . ': Ошибка добавления в очередь. '. $ex->getMessage(), $ex->getTrace());
        }
    }
}
