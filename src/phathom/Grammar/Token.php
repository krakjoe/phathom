<?php declare(strict_types=1);

namespace pharos\phathom\Grammar {
    final class Token extends \pharos\phathom\Token {
        const int IDENT        = 1;
        const int PATTERN      = 2;
        const int STRING       = 3;

        const int QUANTIFIER   = 10;

        const int COLON        = 20;
        const int COMMA        = 21;

        const int LIST_START   = 30;
        const int LIST_END     = 31;

        const int ANNOTATION   = 40;
        const int ACTION       = 50;
        const int PIPE         = 60;
        const int END          = 99;
        const int EOF          = 100;

        public static function string(int $type) : string {
            switch ($type) {
                case Token::IDENT:      return 'IDENT';
                case Token::PATTERN:    return 'PATTERN';
                case Token::STRING:     return 'STRING';
                case Token::QUANTIFIER: return 'QUANTIFIER';
                case Token::COLON:      return 'COLON';
                case Token::COMMA:      return 'COMMA';
                case Token::LIST_START: return 'LIST_START';
                case Token::LIST_END:   return 'LIST_END';
                case Token::ANNOTATION: return 'ANNOTATION';
                case Token::ACTION:     return 'ACTION';
                case Token::PIPE:       return 'PIPE';
                case Token::END:        return 'END';
                case Token::EOF:        return 'EOF';

                default:
                    return 'UNKNOWN';
            }
        }
    }
}
?>