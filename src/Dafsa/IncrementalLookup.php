<?php declare(strict_types=1);

namespace ju1ius\FusBup\Dafsa;

final class IncrementalLookup
{
    /**
     * Pointer to the current position in the graph indicating the current state of the automaton.
     */
    private int $pos = 0;

    /**
     * Whether the if the graph is exhausted.
     */
    private bool $exhausted = false;

    /**
     * Contains the current decoder state.
     * If true, `$pos` points to a label character or a return code.
     * If false, `$pos` points to a sequence of offsets that indicate the child nodes of the current state.
     */
    private bool $atLabelCharacter = false;

    public function __construct(
        private readonly string $graph,
    ) {
    }

    public static function lookup(string $graph, string $key): int
    {
        $lookup = new self($graph);
        // Do an incremental lookup until either the end of the graph is reached,
        // or until every character in $key is consumed.
        for ($i = 0; $i < \strlen($key); $i++) {
            if (!$lookup->advance($key[$i])) {
                return Result::NotFound;
            }
        }
        // The entire input was consumed without reaching the end of the graph.
        // Return the result code (if present) for the current position, or DafsaResult::NotFound.
        return $lookup->getResultForCurrentSequence();
    }

    /**
     * @return array{int, int}
     */
    public static function reverseLookup(string $graph, string $key, bool $includePrivate = true): array
    {
        $lookup = new self($graph);
        $result = Result::NotFound;
        $suffixLength = 0;
        // Look up host from right to left.
        for ($i = \strlen($key) - 1; $i >= 0 && $lookup->advance($key[$i]); $i--) {
            // Only host itself or a part that follows a dot can match.
            if ($i === 0 || $key[$i - 1] === '.') {
                $value = $lookup->getResultForCurrentSequence();
                if ($value !== Result::NotFound) {
                    // Break if private and private rules should be excluded.
                    if (!$includePrivate && ($value & Result::Private)) {
                        break;
                    }
                    // Save length and return value.
                    // Since hosts are looked up from right to left,
                    // the last saved values will be from the longest match.
                    $suffixLength = \strlen($key) - $i;
                    $result = $value;
                }
            }
        }
        return [$result, $suffixLength];
    }

    // Advance the query by adding a character to the input sequence.
    // |input| can be any char value, but only ASCII characters will ever result in matches,
    // since the fixed set itself is limited to ASCII strings.
    //
    // Returns true if the resulting input sequence either appears in the fixed set itself,
    // or is a prefix of some longer string in the fixed set.
    // Returns false otherwise, implying that the graph is exhausted and
    // getResultForCurrentSequence() will return DafsaResult::NotFound.
    //
    // Once advance() has returned false, the caller can safely stop feeding more characters,
    // as subsequent calls to advance() will return false and have no effect.
    public function advance(string $input): bool
    {
        if ($this->exhausted) {
            // A previous input exhausted the graph, so there are no possible matches.
            return false;
        }
        // Only ASCII printable chars are supported by the current DAFSA format
        // -- the high bit (values 0x80-0xFF) is reserved as a label-end signifier,
        // and the low values (values 0x00-0x1F) are reserved to encode the return values.
        // So values outside this range will never be in the dictionary.
        if ($input >= "\x20") {
            if ($this->atLabelCharacter) {
                // Currently processing a label, so it is only necessary to check the byte
                // at $pos to see if it encodes a character matching $input.
                $isLastCharInLabel = $this->isEol($this->pos);
                $isMatch = $this->isMatch($this->pos, $input);
                if ($isMatch) {
                    // If this is not the last character in the label,
                    // the next byte should be interpreted as a character or return value.
                    // Otherwise, the next byte should be interpreted as a list of child node offsets.
                    ++$this->pos;
                    assert($this->pos < \strlen($this->graph));
                    $this->atLabelCharacter = !$isLastCharInLabel;
                    return true;
                }
            } else {
                $offset = $this->pos;
                // Read offsets from $pos until the label of the child node at $offset matches $input,
                // or until there are no more offsets.
                while (null !== $offset = $this->nextOffset($offset)) {
                    assert($offset < \strlen($this->graph));
                    assert($this->exhausted || $this->pos < \strlen($this->graph));
                    // $offset points to a DAFSA node that is a child of the original node.
                    //
                    // The low 7 bits of a node encodes a character value;
                    // the high bit indicates whether it's the last character in the label.
                    //
                    // Note that $offset could also be a result code value,
                    // but these are really just out-of-range ASCII values,
                    // encoded the same way as characters.
                    // Since $input was already validated as a printable ASCII value,
                    // isMatch() will never return true if $offset is a result code.
                    $isLastCharInLabel = $this->isEol($offset);
                    $isMatch = $this->isMatch($offset, $input);
                    if ($isMatch) {
                        // If this is not the last character in the label,
                        // the next byte should be interpreted as a character or return value.
                        // Otherwise, the next byte should be interpreted as a list of child node offsets.
                        $this->exhausted = false;
                        $this->pos = $offset + 1;
                        assert($this->pos < \strlen($this->graph));
                        $this->atLabelCharacter = !$isLastCharInLabel;
                        return true;
                    }
                }
            }
        }

        // If no match was found, then end of the DAFSA has been reached.
        $this->exhausted = true;
        $this->atLabelCharacter = false;
        return false;
    }

    public function getResultForCurrentSequence(): int
    {
        // Look to see if there is a next character that's a return value.
        if ($this->atLabelCharacter) {
            // Currently processing a label, so it is only necessary to check the byte
            // at $pos to see if encodes a return value.
            return $this->getReturnValue($this->pos);
        }
        // Otherwise, $pos is an offset list (or null).
        // Explore the list of child nodes (given by their offsets) to find one whose
        // is a result code.
        //
        // This search uses a temporary copy of $pos, since mutating $pos could
        // skip over a node that would be important to a subsequent advance() call.
        $tmp = $offset = $this->pos;
        $exhausted = $this->exhausted;

        // Read offsets from $tmp until either $tmp is null or until
        // the byte at $offset contains a result code
        // (encoded as an ASCII character below 0x20).
        $result = Result::NotFound;
        while (null !== $offset = $this->nextOffset($offset)) {
            assert($offset < \strlen($this->graph));
            assert($this->exhausted || $this->pos < \strlen($this->graph));
            $result = $this->getReturnValue($offset);
            if ($result !== Result::NotFound) {
                break;
            }
        }

        $this->pos = $tmp;
        $this->exhausted = $exhausted;

        return $result;
    }

    /**
     * Check if byte at $offset is last in label.
     */
    private function isEol(int $offset): bool
    {
        return ($this->graph[$offset] & "\x80") !== "\x00";
    }

    /**
     * Check if byte at $offset matches $key.
     * This version matches both end-of-label chars and not-end-of-label chars.
     */
    private function isMatch(int $offset, string $key): bool
    {
        return ($this->graph[$offset] & "\x7F") === $key;
    }

    private function nextOffset(int $offset): ?int
    {
        if ($this->exhausted) {
            return null;
        }
        switch ($this->graph[$this->pos] & "\x60") {
            case "\x60":
                // Read three byte offset
                $offset += (ord($this->graph[$this->pos] & "\x1F") << 16)
                    | (ord($this->graph[$this->pos + 1]) << 8)
                    | ord($this->graph[$this->pos + 2])
                ;
                $bytesConsumed = 3;
                break;
            case "\x40":
                // Read two byte offset
                $offset += (ord($this->graph[$this->pos] & "\x1F") << 8)
                    | ord($this->graph[$this->pos + 1])
                ;
                $bytesConsumed = 2;
                break;
            default:
                $offset += ord($this->graph[$this->pos] & "\x3F");
                $bytesConsumed = 1;
                break;
        }
        if (($this->graph[$this->pos] & "\x80") !== "\x00") {
            $this->exhausted = true;
        } else {
            $this->pos += $bytesConsumed;
        }

        return $offset;
    }

    /**
     * Read return value at |offset|, if it is a return value.
     * Returns true if a return value could be read, false otherwise.
     */
    private function getReturnValue(int $offset): int
    {
        // Return values are always encoded as end-of-label chars (so the high bit is set).
        // So byte values in the inclusive range [0x80, 0x9F] encode the return values 0 through 31
        // The following code does that translation.
        if (($this->graph[$offset] & "\xE0") === "\x80") {
            return \ord($this->graph[$offset] & "\x1F");
        }

        return Result::NotFound;
    }
}
