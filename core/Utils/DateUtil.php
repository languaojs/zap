<?php

namespace Zap\Core\Utils;

use DateTime;
use DateTimeZone;
use Exception;

class DateUtil
{
    protected static string $defaultTimezone = 'Asia/Jakarta';

    /**
     * Mendapatkan instance DateTime saat ini sesuai zona waktu default.
     * 
     * @return DateTime
     */
    public static function now(): DateTime
    {
        return new DateTime('now', new DateTimeZone(self::$defaultTimezone));
    }

    /**
     * Mengubah berbagai format tanggal/waktu menjadi string format SQL Date (Y-m-d).
     * 
     * @param DateTime|string|null $date
     * @return string
     * @throws Exception
     */
    public static function toSqlDate(DateTime|string|null $date = 'now'): string
    {
        $dt = self::toDateTime($date);
        return $dt->format('Y-m-d');
    }

    /**
     * Mengubah berbagai format tanggal/waktu menjadi string format SQL DateTime (Y-m-d H:i:s).
     * 
     * @param DateTime|string|null $date
     * @return string
     * @throws Exception
     */
    public static function toSqlDateTime(DateTime|string|null $date = 'now'): string
    {
        $dt = self::toDateTime($date);
        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Helper internal untuk mengonversi input menjadi objek DateTime secara aman (immutable).
     * 
     * @param DateTime|string|null $date
     * @return DateTime
     * @throws Exception
     */
    public static function toDateTime(DateTime|string|null $date = 'now'): DateTime
    {
        if ($date instanceof DateTime) {
            return clone $date;
        }

        if (empty($date)) {
            $date = 'now';
        }

        return new DateTime($date, new DateTimeZone(self::$defaultTimezone));
    }

    /**
     * Memodifikasi tanggal dengan string modifier bebas (misal: '+7 days', '-2 hours', '+1 month -3 days').
     * 
     * @param DateTime|string|null $date
     * @param string $modifier
     * @return DateTime
     * @throws Exception
     */
    public static function modify(DateTime|string|null $date, string $modifier): DateTime
    {
        $dt = self::toDateTime($date);
        $dt->modify($modifier);
        return $dt;
    }

    /**
     * Menambahkan atau mengurangi hari (gunakan angka negatif untuk pengurangan, cth: -7).
     * 
     * @param DateTime|string|null $date
     * @param int $days
     * @return DateTime
     * @throws Exception
     */
    public static function addDays(DateTime|string|null $date, int $days): DateTime
    {
        $modifier = ($days >= 0) ? "+{$days} days" : "{$days} days";
        return self::modify($date, $modifier);
    }

    /**
     * Menambahkan atau mengurangi jam (gunakan angka negatif untuk pengurangan, cth: -2).
     * 
     * @param DateTime|string|null $date
     * @param int $hours
     * @return DateTime
     * @throws Exception
     */
    public static function addHours(DateTime|string|null $date, int $hours): DateTime
    {
        $modifier = ($hours >= 0) ? "+{$hours} hours" : "{$hours} hours";
        return self::modify($date, $modifier);
    }

    /**
     * Mengecek apakah suatu tanggal berada di antara rentang tanggal tertentu.
     * 
     * @param DateTime|string|null $date
     * @param DateTime|string|null $start
     * @param DateTime|string|null $end
     * @return bool
     * @throws Exception
     */
    public static function isBetween(DateTime|string|null $date, DateTime|string|null $start, DateTime|string|null $end): bool
    {
        $target = self::toDateTime($date);
        $startDate = self::toDateTime($start);
        $endDate = self::toDateTime($end);

        return $target >= $startDate && $target <= $endDate;
    }

    /**
     * Memformat tanggal ke dalam bentuk bahasa Indonesia standar (contoh: 05 September 2026).
     * 
     * @param DateTime|string|null $date
     * @return string
     * @throws Exception
     */
    public static function formatIndonesian(DateTime|string|null $date): string
    {
        $dt = self::toDateTime($date);

        $months = [
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember'
        ];

        $day = $dt->format('d');
        $month = $months[(int)$dt->format('n')];
        $year = $dt->format('Y');

        return "{$day} {$month} {$year}";
    }

    /**
     * Mengurangi jumlah hari.
     * 
     * @param DateTime|string|null $date
     * @param int $days
     * @return DateTime
     * @throws Exception
     */
    public static function subDays(DateTime|string|null $date, int $days): DateTime
    {
        return self::addDays($date, -abs($days));
    }

    /**
     * Mengurangi jumlah jam.
     * 
     * @param DateTime|string|null $date
     * @param int $hours
     * @return DateTime
     * @throws Exception
     */
    public static function subHours(DateTime|string|null $date, int $hours): DateTime
    {
        return self::addHours($date, -abs($hours));
    }

    /**
     * Menghitung detail usia (tahun, bulan, hari) dari tanggal tertentu.
     * 
     * @param DateTime|string|null $birthDate
     * @param DateTime|string|null $targetDate Default ke waktu sekarang
     * @return array<string, int>
     * @throws Exception
     */
    public static function age(DateTime|string|null $birthDate, DateTime|string|null $targetDate = 'now'): array
    {
        $birth = self::toDateTime($birthDate);
        $target = self::toDateTime($targetDate);
        $interval = $birth->diff($target);

        return [
            'years'  => $interval->y,
            'months' => $interval->m,
            'days'   => $interval->d,
        ];
    }
}
