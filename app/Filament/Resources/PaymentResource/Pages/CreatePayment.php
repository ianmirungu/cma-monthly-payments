<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Member;
use App\Models\Payment;
use DateTime;
use Exception;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    private function getStartingMonth(Member $member): DateTime|false
    {
        $latestPayment = $member->payments()->latest()->first();

        $month =  $latestPayment ? $latestPayment->latest_month : '2020-12';

        return DateTime::createFromFormat('Y-m', $month);
    }

    private function getMonthsCoveredInCurrentPayment($amountPaid)
    {
        $months = intdiv($amountPaid, 200);

        return $months == 0 ? 1 : $months;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $member = Member::find($data['member_id']);
        $currDate = new DateTime();
        $amountPaid = $data['amount'];

        // Get latest month paid for prior to this payment
        $lastMonthPaidFor = $this->getStartingMonth($member);

        // Integer division of amount to get number of months paid for
        $countOfMonthsPaid = $this->getMonthsCoveredInCurrentPayment($amountPaid);

        // Get amount remaining after integer division
        $modulo = $amountPaid % 200;
        $remainingAmount = 200 - $modulo;

        // Increment the latest month by the number of months paid for
        $newStartMonth = (clone $lastMonthPaidFor)->modify("+1 month");
        $newLastMonth = $lastMonthPaidFor->modify("+{$countOfMonthsPaid} months");

        if ($modulo > 0) {
            $latestMonthPaidInFull = false;
            $latestMonthAmountPending = $remainingAmount;
            $newLastMonth->modify("+1 month"); // Push extra to the next month
        } else {
            $latestMonthPaidInFull = true;
            $latestMonthAmountPending = 0;
        }

        // Determine the comment to display
        if ($newStartMonth == $newLastMonth) {
            $comment = $newStartMonth->format('M Y');
        } else {
            $comment = $newStartMonth->modify("+1")->format('M Y') . " - "  . $newLastMonth->format('M Y');
        }

        $latestMonthPretty = $lastMonthPaidFor->format('M Y');

        if (!$latestMonthPaidInFull) {
            $comment .= " (Only: {$modulo})";
            $latestMonthPretty .= " (Owe: {$latestMonthAmountPending})";
        }

        $interval = $currDate->diff($newLastMonth);
        $amountOwed = (($interval->y * 12 + $interval->m) * 200) + $latestMonthAmountPending;

        // Custom record creation logic
        $newData = array_merge($data, [
            'latest_month' => $lastMonthPaidFor->format('Y-m'),
            'latest_month_paid_in_full' => $latestMonthPaidInFull,
            'latest_month_amount_pending' => $latestMonthAmountPending,
            'latest_month_pretty' => $latestMonthPretty,
            'comment' => $comment,
            'amount_owed' => $amountOwed,
        ]);

//        dd($newData);

        return Payment::create($newData);
    }

    private function incrementMonthYear(string $monthYear, int $increment): string
    {
        // Create a DateTime object from the input string
        $date = DateTime::createFromFormat('Y-m', $monthYear);

        // Check if the date was created successfully
        if (!$date) {
            throw new Exception('Invalid date format');
        }

        // Modify the date by adding the increment in months
        $date->modify("+{$increment} months");

        // Return the new date in the same format
        return $date->format('Y-m');
    }

}
