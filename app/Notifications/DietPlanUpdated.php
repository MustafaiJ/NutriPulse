<?php

namespace App\Notifications;

use App\Models\DietPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DietPlanUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public DietPlan $plan,
        public string $action,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! app()->isLocal() && config('mail.mailers.'.config('mail.default').'.host') !== 'log') {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(ucfirst($this->action).' — Your diet plan has been updated')
            ->greeting('Hi '.$notifiable->name.',')
            ->line("Your dietitian has {$this->action} your diet plan: **{$this->plan->title}**.")
            ->when(filled($this->plan->target_calories), fn (MailMessage $m) => $m->line('Target calories: '.$this->plan->target_calories.' kcal'))
            ->action('View Diet Plan', route('diet-plans.index'))
            ->line('Thank you for staying on track!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'plan_id' => $this->plan->id,
            'plan_title' => $this->plan->title,
            'action' => $this->action,
            'message' => "Your dietitian has {$this->action} your diet plan: {$this->plan->title}.",
        ];
    }
}
