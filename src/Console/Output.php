<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Console;

use Stateforge\Scenario\Core\Console\Output\Formatter\Align;
use Stateforge\Scenario\Core\Contract\CliOutput;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use function is_scalar;
use function is_string;
use const STR_PAD_BOTH;
use const STR_PAD_LEFT;
use const STR_PAD_RIGHT;

final class Output implements CliOutput
{
    public function __construct(private SymfonyStyle $style)
    {
    }

    public function confirm(string $question, bool $default = true): bool
    {
        $this->style->newLine();
        return $this->style->confirm($question, $default);
    }

    public function headline(string $text): void
    {
        $this->style->newLine();
        $this->style->section($text);
    }

    public function success(string $text): void
    {
        $this->style->newLine();
        $this->style->success($text);
    }

    public function warn(string $text): void
    {
        $this->style->newLine();
        $this->style->warning($text);
    }

    public function error(string $text): void
    {
        $this->style->newLine();
        $this->style->error($text);
    }

    public function note(string $text): void
    {
        $this->style->newLine();
        $this->style->note($text);
    }

    /**
     * @param list<string>|null $headers
     * @param list<list<string|null>> $rows
     * @param list<Align>|null $align
     */
    public function table(?array $headers, array $rows, ?array $align = null, bool $showBorder = true): void
    {
        $table = $this->style->createTable();

        if ($headers !== null) {
            $table->setHeaders($headers);
        }

        $table->setRows($rows);
        if ($align !== null) {
            foreach ($align as $column => $alignType) {
                $table->setColumnStyle(
                    $column,
                    match ($alignType) {
                        Align::Right => (new TableStyle())->setPadType(STR_PAD_RIGHT),
                        Align::Center => (new TableStyle())->setPadType(STR_PAD_BOTH),
                        default => (new TableStyle())->setPadType(STR_PAD_LEFT),
                    },
                );
            }
        }

        if ($showBorder === true) {
            $table->setStyle((new TableStyle())
                ->setHorizontalBorderChars('─')
                ->setVerticalBorderChars(' ')
                ->setCrossingChars(' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '));
        } else {
            $table->setStyle('borderless');
        }

        $this->style->newLine();
        $table->render();
        $this->style->newLine();
    }

    public function question(string $text): void
    {
        $this->style->newLine();
        $this->style->block($text, null, 'fg=white;bg=blue', ' ', true);
        $this->style->newLine();
    }

    public function choice(string $question, array $choices, ?string $default = null): string
    {
        $this->question($question);
        $answer = $this->style->choice('Please select one of the following:', $choices, $default);
        $this->style->newLine();

        return is_string($answer) === true
            ? $answer
            : '';
    }

    public function ask(string $question, ?string $default = null, ?callable $validator = null): ?string
    {
        $this->style->newLine();
        $answer = $this->style->ask($question, $default, $validator);
        while ($answer === false) {
            $answer = $this->style->ask('<error>Input was invalid, please try again</error>', $default, $validator);
        }

        return is_scalar($answer) === true || $answer === null
            ? (string) $answer
            : null;
    }

    /**
     * @param string|list<string> $string
     */
    public function writeln(string|array $string): void
    {
        if (is_string($string)) {
            $string = [ $string ];
        }

        foreach ($string as $line) {
            $this->style->writeln($line);
        }
    }
}
