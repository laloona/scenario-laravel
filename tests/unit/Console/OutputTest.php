<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Laravel package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Laravel\Tests\Unit\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Core\Console\Output\Formatter\Align;
use Stateforge\Scenario\Laravel\Console\Output;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use const STR_PAD_BOTH;
use const STR_PAD_LEFT;
use const STR_PAD_RIGHT;

#[CoversClass(Output::class)]
#[Group('console')]
#[Small]
final class OutputTest extends TestCase
{
    public function testConfirmDelegatesToSymfonyStyle(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('confirm')
            ->with('Are you sure?', true)
            ->willReturn(false);

        self::assertFalse((new Output($style))->confirm('Are you sure?', true));
    }

    public function testHeadlineUsesSection(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('section')
            ->with('Hello');

        (new Output($style))->headline('Hello');
    }

    public function testSuccessDelegates(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('success')
            ->with('OK');

        (new Output($style))->success('OK');
    }

    public function testErrorDelegates(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('error')
            ->with('Error');

        (new Output($style))->error('Error');
    }

    public function testWarnDelegates(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('warning')
            ->with('Warn');

        (new Output($style))->warn('Warn');
    }

    public function testNoteDelegates(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('note')
            ->with('Helpful information');

        (new Output($style))->note('Helpful information');
    }

    public function testQuestionUsesBlockAndNewLine(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->exactly(2))
            ->method('newLine');
        $style->expects($this->once())
            ->method('block')
            ->with('My Question?', null, 'fg=white;bg=blue', ' ', true);

        (new Output($style))->question('My Question?');
    }

    public function testChoiceAsksQuestionThenChoiceThenNewLine(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->exactly(3))
            ->method('newLine');

        $style->expects($this->once())
            ->method('block')
            ->with('Select one', null, 'fg=white;bg=blue', ' ', true);

        $style->expects($this->once())
            ->method('choice')
            ->with('Please select one of the following:', ['a', 'b'], 'b')
            ->willReturn('a');

        self::assertSame('a', (new Output($style))->choice('Select one', ['a', 'b'], 'b'));
    }

    public function testChoiceReturnsEmptyStringForNonStringAnswer(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->exactly(3))
            ->method('newLine');

        $style->expects($this->once())
            ->method('block')
            ->with('Select one', null, 'fg=white;bg=blue', ' ', true);

        $style->expects($this->once())
            ->method('choice')
            ->with('Please select one of the following:', ['a', 'b'], null)
            ->willReturn(['a']);

        self::assertSame('', (new Output($style))->choice('Select one', ['a', 'b']));
    }

    public function testAskReturnsAnswerWhenValid(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('ask')
            ->with('Name?', 'OtherName', null)
            ->willReturn('MyName');

        self::assertSame('MyName', (new Output($style))->ask('Name?', 'OtherName'));
    }

    public function testAskRepeatsWhenAnswerIsFalse(): void
    {
        $style = $this->createMock(SymfonyStyle::class);

        $questions = [];
        $validator = static fn (mixed $value): mixed => $value;
        $style->expects(self::once())->method('newLine');
        $style->expects(self::exactly(2))
            ->method('ask')
            ->willReturnCallback(function (string $question, ?string $default, ?callable $actualValidator) use (&$questions, $validator): string|false {
                $questions[] = $question;
                self::assertSame('Default', $default);
                self::assertSame($validator, $actualValidator);
                return $question === 'Name?' ? false : 'OK';
            });

        $output = new Output($style);
        self::assertSame('OK', $output->ask('Name?', 'Default', $validator));
        self::assertSame(['Name?', '<error>Input was invalid, please try again</error>'], $questions);
    }

    #[DataProvider('answerProvider')]
    public function testAskConvertsScalarAndNullAnswersToString(mixed $answer, string $expected): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects(self::once())->method('newLine');
        $style->expects(self::once())->method('ask')->with('Value?', null, null)->willReturn($answer);

        self::assertSame($expected, (new Output($style))->ask('Value?'));
    }

    /** @return array<string, array{mixed, string}> */
    public static function answerProvider(): array
    {
        return [
            'integer' => [42, '42'],
            'float' => [1.5, '1.5'],
            'boolean' => [true, '1'],
            'null' => [null, ''],
        ];
    }

    public function testAskReturnsNullForNonScalarAnswer(): void
    {
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('newLine');
        $style->expects($this->once())
            ->method('ask')
            ->with('Name?', null, null)
            ->willReturn(['x']);

        self::assertNull((new Output($style))->ask('Name?'));
    }

    public function testTableSetsHeadersRowsAlignBorderAndRenders(): void
    {
        $table = $this->createMock(Table::class);

        $table->expects($this->once())
            ->method('setHeaders')
            ->with(['H1', 'H2'])
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('setRows')
            ->with([['a', 'b']])
            ->willReturnSelf();

        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->once())
            ->method('createTable')
            ->willReturn($table);

        $table->expects($this->exactly(2))
            ->method('setColumnStyle')
            ->with(
                self::logicalOr(self::equalTo(0), self::equalTo(1)),
                self::callback(function (mixed $arg): bool {
                    return $arg instanceof TableStyle;
                }),
            )
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('setStyle')
            ->with(self::isInstanceOf(TableStyle::class))
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('render');

        $style->expects($this->exactly(2))
            ->method('newLine');

        (new Output($style))->table(
            headers: ['H1', 'H2'],
            rows: [['a', 'b']],
            align: [0 => Align::Left, 1 => Align::Center],
            showBorder: true,
        );
    }

    #[DataProvider('alignmentProvider')]
    public function testTableAppliesColumnPadding(Align $alignment, int $padding): void
    {
        $table = $this->createMock(Table::class);
        $table->expects(self::once())->method('setRows')->with([['cell']])->willReturnSelf();
        $table->expects(self::once())->method('setColumnStyle')
            ->with(0, self::callback(static fn (TableStyle $style): bool => $style->getPadType() === $padding))
            ->willReturnSelf();
        $table->expects(self::once())->method('render');
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects(self::exactly(2))->method('newLine');
        $style->expects(self::once())->method('createTable')->willReturn($table);

        (new Output($style))->table(null, [['cell']], [$alignment]);
    }

    /** @return array<string, array{Align, int}> */
    public static function alignmentProvider(): array
    {
        return [
            'left' => [Align::Left, STR_PAD_LEFT],
            'center' => [Align::Center, STR_PAD_BOTH],
            'right' => [Align::Right, STR_PAD_RIGHT],
        ];
    }

    public function testTableWithoutHeadersDoesNotCallSetHeaders(): void
    {
        $table = $this->createMock(Table::class);
        $table->expects(self::never())
            ->method('setHeaders');

        $table->expects($this->once())
            ->method('setRows')
            ->with([['cell']])
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('setStyle')
            ->with(self::isInstanceOf(TableStyle::class))
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('render');

        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->exactly(2))
            ->method('newLine');

        $style->expects($this->once())
            ->method('createTable')
            ->willReturn($table);

        (new Output($style))->table(null, [['cell']]);
    }

    public function testTableBorderlessUsesBorderlessStyleString(): void
    {
        $table = $this->createMock(Table::class);
        $table->expects($this->once())
            ->method('setRows')
            ->with([['cell']])
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('setStyle')
            ->with('borderless')
            ->willReturnSelf();

        $table->expects($this->once())
            ->method('render');

        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->exactly(2))
            ->method('newLine');

        $style->expects($this->once())
            ->method('createTable')
            ->willReturn($table);

        (new Output($style))->table(null, [['cell']], null, false);
    }

    public function testWritelnWritesLinesForStringAndArray(): void
    {
        $lines = [];
        $style = $this->createMock(SymfonyStyle::class);
        $style->expects($this->exactly(3))
            ->method('writeln')
            ->willReturnCallback(static function (string $line) use (&$lines): void {
                $lines[] = $line;
            });

        $output = new Output($style);
        $output->writeln('one');
        $output->writeln(['two', 'three']);
        self::assertSame(['one', 'two', 'three'], $lines);
    }
}
