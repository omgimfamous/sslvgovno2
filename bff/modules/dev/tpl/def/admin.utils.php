<?php
    tplAdmin::adminPageSettings(array('icon'=>false));
?>

<script type="text/javascript">
//<![CDATA[
var jDevUtils = (function(){

    var curTab = '', $tabs;

    $(function(){
        $tabs = $('.dev-u-tab');
        onTab('<?= $aData['tab'] ?>');
    });

    function onTab( tab )
    {
        if(curTab == tab)
            return;

        $tabs.hide();
        var $tab = $tabs.filter('#dev-u-tab-'+tab).show();
        switch(tab) {
            case 'sysword': {
                $tab.find('#word').focus();
            } break;
        }

        $('#tabs span.tab').removeClass('tab-active');
        $('#tabs span[rel="'+tab+'"]').addClass('tab-active');

        curTab = document.getElementById('tab').value = tab;
    }

    return {
        onTab: onTab
    }
}());
//]]>
</script>

<form method="post" action="">
<input type="hidden" name="save" value="1" />
<input type="hidden" name="tab" id="tab" value="" />

<div class="tabsBar" id="tabs">
    <?php foreach($tabs as $k=>$v): ?>
        <span class="tab" onclick="jDevUtils.onTab('<?= $k ?>');" rel="<?= $k ?>"><?= $v['t'] ?></span>
    <?php endforeach; ?>
</div>

<!-- sysword -->
<div id="dev-u-tab-sysword" class="dev-u-tab">
    <table class="admtbl tbledit" style="margin:10px 0 10px 10px; border-collapse:separate;">
    <tr>
        <td class="row1">
            <p class="text-info">Укажите слово для проверки:</p>
            <input type="text" id="word" size="30" value="" />
            <span class="clr-error text-error" id="word-sys" style="display:none;"> - системное</span>
            <span class="clr-success text-success" id="word-ok" style="display:none;"> - можно использовать</span>
        </td>
    </tr>
    <tr class="footer">
        <td class="row1">
            <input type="button" class="btn btn-success button submit" value="Проверить" onclick="devCheckIsSystemWord();" />
        </td>
    </tr>
    </table>
    <script type="text/javascript">
    //<![CDATA[
    function devCheckIsSystemWord()
    {
        $('#word-sys, #word-ok').hide();
        var word = $('#word').val();
        if(!word){ $('#word').focus(); return; }
        $('#word-'+( devIsSystemWord( word.toUpperCase() ) ?'sys':'ok')).fadeIn();
    }

    function devIsSystemWord(word)
    {
        var sys = ['A', 'ABORT', 'ABS', 'ABSOLUTE', 'ACCESS', 'ACTION', 'ADA', 'ADD', 'ADMIN', 'AFTER', 'AGGREGATE', 'ALIAS', 'ALL', 'ALLOCATE', 'ALSO', 'ALTER',
         'ALWAYS', 'ANALYSE', 'ANALYZE', 'AND', 'ANY', 'ARE', 'ARRAY', 'AS', 'ASC', 'ASENSITIVE', 'ASSERTION', 'ASSIGNMENT', 'ASYMMETRIC', 'AT', 'ATOMIC',
         'ATTRIBUTE', 'ATTRIBUTES', 'AUDIT', 'AUTHORIZATION', 'AUTO', 'INCREMENT', 'AVG', 'AVG', 'ROW', 'LENGTH', 'BACKUP', 'BACKWARD', 'BEFORE', 'BEGIN',
         'BERNOULLI', 'BETWEEN', 'BIGINT', 'BINARY', 'BIT', 'BIT', 'LENGTH', 'BITVAR', 'BLOB', 'BOOL', 'BOOLEAN', 'BOTH', 'BREADTH', 'BREAK', 'BROWSE',
         'BULK', 'BY', 'C', 'CACHE', 'CALL', 'CALLED', 'CARDINALITY', 'CASCADE', 'CASCADED', 'CASE', 'CAST', 'CATALOG', 'CATALOG', 'NAME', 'CEIL',
         'CEILING', 'CHAIN', 'CHANGE', 'CHAR', 'CHAR', 'LENGTH', 'CHARACTER', 'CHARACTER', 'LENGTH', 'CHARACTER', 'SET', 'CATALOG', 'CHARACTER', 'SET',
         'NAME', 'CHARACTER', 'SET', 'SCHEMA', 'CHARACTERISTICS', 'CHARACTERS', 'CHECK', 'CHECKED', 'CHECKPOINT', 'CHECKSUM', 'CLASS', 'CLASS', 'ORIGIN',
         'CLOB', 'CLOSE', 'CLUSTER', 'CLUSTERED', 'COALESCE', 'COBOL', 'COLLATE', 'COLLATION', 'COLLATION', 'CATALOG', 'COLLATION', 'NAME', 'COLLATION',
         'SCHEMA', 'COLLECT', 'COLUMN', 'COLUMN', 'NAME', 'COLUMNS', 'COMMAND', 'FUNCTION', 'COMMAND', 'FUNCTION', 'CODE', 'COMMENT', 'COMMIT', 'COMMITTED',
         'COMPLETION', 'COMPRESS', 'COMPUTE', 'CONDITION', 'CONDITION', 'NUMBER', 'CONNECT', 'CONNECTION', 'CONNECTION', 'NAME', 'CONSTRAINT', 'CONSTRAINT',
         'CATALOG', 'CONSTRAINT', 'NAME', 'CONSTRAINT', 'SCHEMA', 'CONSTRAINTS', 'CONSTRUCTOR', 'CONTAINS', 'CONTAINSTABLE', 'CONTINUE', 'CONVERSION',
         'CONVERT', 'COPY', 'CORR', 'CORRESPONDING', 'COUNT', 'COVAR', 'POP', 'COVAR', 'SAMP', 'CREATE', 'CREATEDB', 'CREATEROLE', 'CREATEUSER', 'CROSS',
         'CSV', 'CUBE', 'CUME', 'DIST', 'CURRENT', 'CURRENT', 'DATE', 'CURRENT', 'DEFAULT', 'TRANSFORM', 'GROUP', 'CURRENT', 'PATH', 'CURRENT', 'ROLE',
         'CURRENT', 'TIME', 'CURRENT', 'TIMESTAMP', 'CURRENT', 'TRANSFORM', 'GROUP', 'FOR', 'TYPE', 'CURRENT', 'USER', 'CURSOR', 'CURSOR', 'NAME', 'CYCLE',
         'DATA', 'DATABASE', 'DATABASES', 'DATE', 'DATETIME', 'DATETIME', 'INTERVAL', 'CODE', 'DATETIME', 'INTERVAL', 'PRECISION', 'DAY', 'DAY', 'HOUR',
         'DAY', 'MICROSECOND', 'DAY', 'MINUTE', 'DAY', 'SECOND', 'DAYOFMONTH', 'DAYOFWEEK', 'DAYOFYEAR', 'DBCC', 'DEALLOCATE', 'DEC', 'DECIMAL', 'DECLARE',
         'DEFAULT', 'DEFAULTS', 'DEFERRABLE', 'DEFERRED', 'DEFINED', 'DEFINER', 'DEGREE', 'DELAY', 'KEY', 'WRITE', 'DELAYED', 'DELETE', 'DELIMITER',
         'DELIMITERS', 'DENSE', 'RANK', 'DENY', 'DEPTH', 'DEREF', 'DERIVED', 'DESC', 'DESCRIBE', 'DESCRIPTOR', 'DESTROY', 'DESTRUCTOR', 'DETERMINISTIC',
         'DIAGNOSTICS', 'DICTIONARY', 'DISABLE', 'DISCONNECT', 'DISK', 'DISPATCH', 'DISTINCT', 'DISTINCTROW', 'DISTRIBUTED', 'DIV', 'DO', 'DOMAIN',
         'DOUBLE', 'DROP', 'DUAL', 'DUMMY', 'DUMP', 'DYNAMIC', 'DYNAMIC', 'FUNCTION', 'DYNAMIC', 'FUNCTION', 'CODE', 'EACH', 'ELEMENT', 'ELSE', 'ELSEIF',
         'ENABLE', 'ENCLOSED', 'ENCODING', 'ENCRYPTED', 'END', 'END', 'EXEC', 'ENUM', 'EQUALS', 'ERRLVL', 'ESCAPE', 'ESCAPED', 'EVERY', 'EXCEPT', 'EXCEPTION',
         'EXCLUDE', 'EXCLUDING', 'EXCLUSIVE', 'EXEC', 'EXECUTE', 'EXISTING', 'EXISTS', 'EXIT', 'EXP', 'EXPLAIN', 'EXTERNAL', 'EXTRACT', 'FALSE', 'FETCH',
         'FIELDS', 'FILE', 'FILLFACTOR', 'FILTER', 'FINAL', 'FIRST', 'FLOAT', 'FLOAT', 'FLOAT', 'FLOOR', 'FLUSH', 'FOLLOWING', 'FOR', 'FORCE', 'FOREIGN',
         'FORTRAN', 'FORWARD', 'FOUND', 'FREE', 'FREETEXT', 'FREETEXTTABLE', 'FREEZE', 'FROM', 'FULL', 'FULLTEXT', 'FUNCTION', 'FUSION', 'G', 'GENERAL',
         'GENERATED', 'GET', 'GLOBAL', 'GO', 'GOTO', 'GRANT', 'GRANTED', 'GRANTS', 'GREATEST', 'GROUP', 'GROUPING', 'HANDLER', 'HAVING', 'HEADER', 'HEAP',
         'HIERARCHY', 'HIGH', 'PRIORITY', 'HOLD', 'HOLDLOCK', 'HOST', 'HOSTS', 'HOUR', 'HOUR', 'MICROSECOND', 'HOUR', 'MINUTE', 'HOUR', 'SECOND', 'IDENTIFIED',
         'IDENTITY', 'IDENTITY', 'INSERT', 'IDENTITYCOL', 'IF', 'IGNORE', 'ILIKE', 'IMMEDIATE', 'IMMUTABLE', 'IMPLEMENTATION', 'IMPLICIT', 'IN', 'INCLUDE',
         'INCLUDING', 'INCREMENT', 'INDEX', 'INDICATOR', 'INFILE', 'INFIX', 'INHERIT', 'INHERITS', 'INITIAL', 'INITIALIZE', 'INITIALLY', 'INNER', 'INOUT',
         'INPUT', 'INSENSITIVE', 'INSERT', 'INSERT', 'ID', 'INSTANCE', 'INSTANTIABLE', 'INSTEAD', 'INT', 'INT', 'INT', 'INT', 'INT', 'INT', 'INTEGER',
         'INTERSECT', 'INTERSECTION', 'INTERVAL', 'INTO', 'INVOKER', 'IS', 'ISAM', 'ISNULL', 'ISOLATION', 'ITERATE', 'JOIN', 'K', 'KEY', 'KEY', 'MEMBER',
         'KEY', 'TYPE', 'KEYS', 'KILL', 'LANCOMPILER', 'LANGUAGE', 'LARGE', 'LAST', 'LAST', 'INSERT', 'ID', 'LATERAL', 'LEADING', 'LEAST', 'LEAVE', 'LEFT',
         'LENGTH', 'LESS', 'LEVEL', 'LIKE', 'LIMIT', 'LINENO', 'LINES', 'LISTEN', 'LN', 'LOAD', 'LOCAL', 'LOCALTIME', 'LOCALTIMESTAMP', 'LOCATION', 'LOCATOR',
         'LOCK', 'LOGIN', 'LOGS', 'LONG', 'LONGBLOB', 'LONGTEXT', 'LOOP', 'LOW', 'PRIORITY', 'LOWER', 'M', 'MAP', 'MATCH', 'MATCHED', 'MAX', 'MAX', 'ROWS',
         'MAXEXTENTS', 'MAXVALUE', 'MEDIUMBLOB', 'MEDIUMINT', 'MEDIUMTEXT', 'MEMBER', 'MERGE', 'MESSAGE', 'LENGTH', 'MESSAGE', 'OCTET', 'LENGTH', 'MESSAGE',
         'TEXT', 'METHOD', 'MIDDLEINT', 'MIN', 'MIN', 'ROWS', 'MINUS', 'MINUTE', 'MINUTE', 'MICROSECOND', 'MINUTE', 'SECOND', 'MINVALUE', 'MLSLABEL', 'MOD',
         'MODE', 'MODIFIES', 'MODIFY', 'MODULE', 'MONTH', 'MONTHNAME', 'MORE', 'MOVE', 'MULTISET', 'MUMPS', 'MYISAM', 'NAME', 'NAMES', 'NATIONAL', 'NATURAL',
         'NCHAR', 'NCLOB', 'NESTING', 'NEW', 'NEXT', 'NO', 'NO', 'WRITE', 'TO', 'BINLOG', 'NOAUDIT', 'NOCHECK', 'NOCOMPRESS', 'NOCREATEDB', 'NOCREATEROLE',
         'NOCREATEUSER', 'NOINHERIT', 'NOLOGIN', 'NONCLUSTERED', 'NONE', 'NORMALIZE', 'NORMALIZED', 'NOSUPERUSER', 'NOT', 'NOTHING', 'NOTIFY', 'NOTNULL',
         'NOWAIT', 'NULL', 'NULLABLE', 'NULLIF', 'NULLS', 'NUMBER', 'NUMERIC', 'OBJECT', 'OCTET', 'LENGTH', 'OCTETS', 'OF', 'OFF', 'OFFLINE', 'OFFSET',
         'OFFSETS', 'OIDS', 'OLD', 'ON', 'ONLINE', 'ONLY', 'OPEN', 'OPENDATASOURCE', 'OPENQUERY', 'OPENROWSET', 'OPENXML', 'OPERATION', 'OPERATOR', 'OPTIMIZE',
         'OPTION', 'OPTIONALLY', 'OPTIONS', 'OR', 'ORDER', 'ORDERING', 'ORDINALITY', 'OTHERS', 'OUT', 'OUTER', 'OUTFILE', 'OUTPUT', 'OVER', 'OVERLAPS',
         'OVERLAY', 'OVERRIDING', 'OWNER', 'PACK', 'KEYS', 'PAD', 'PARAMETER', 'PARAMETER', 'MODE', 'PARAMETER', 'NAME', 'PARAMETER', 'ORDINAL', 'POSITION',
         'PARAMETER', 'SPECIFIC', 'CATALOG', 'PARAMETER', 'SPECIFIC', 'NAME', 'PARAMETER', 'SPECIFIC', 'SCHEMA', 'PARAMETERS', 'PARTIAL', 'PARTITION',
         'PASCAL', 'PASSWORD', 'PATH', 'PCTFREE', 'PERCENT', 'PERCENT', 'RANK', 'PERCENTILE', 'CONT', 'PERCENTILE', 'DISC', 'PLACING', 'PLAN', 'PLI',
         'POSITION', 'POSTFIX', 'POWER', 'PRECEDING', 'PRECISION', 'PREFIX', 'PREORDER', 'PREPARE', 'PREPARED', 'PRESERVE', 'PRIMARY', 'PRINT', 'PRIOR',
         'PRIVILEGES', 'PROC', 'PROCEDURAL', 'PROCEDURE', 'PROCESS', 'PROCESSLIST', 'PUBLIC', 'PURGE', 'QUOTE', 'RAID', 'RAISERROR', 'RANGE', 'RANK', 'RAW',
         'READ', 'READS', 'READTEXT', 'REAL', 'RECHECK', 'RECONFIGURE', 'RECURSIVE', 'REF', 'REFERENCES', 'REFERENCING', 'REGEXP', 'REGR', 'AVGX', 'REGR',
         'AVGY', 'REGR', 'COUNT', 'REGR', 'INTERCEPT', 'REGR', 'R', 'REGR', 'SLOPE', 'REGR', 'SXX', 'REGR', 'SXY', 'REGR', 'SYY', 'REINDEX', 'RELATIVE',
         'RELEASE', 'RELOAD', 'RENAME', 'REPEAT', 'REPEATABLE', 'REPLACE', 'REPLICATION', 'REQUIRE', 'RESET', 'RESIGNAL', 'RESOURCE', 'RESTART', 'RESTORE',
         'RESTRICT', 'RESULT', 'RETURN', 'RETURNED', 'CARDINALITY', 'RETURNED', 'LENGTH', 'RETURNED', 'OCTET', 'LENGTH', 'RETURNED', 'SQLSTATE', 'RETURNS',
         'REVOKE', 'RIGHT', 'RLIKE', 'ROLE', 'ROLLBACK', 'ROLLUP', 'ROUTINE', 'ROUTINE', 'CATALOG', 'ROUTINE', 'NAME', 'ROUTINE', 'SCHEMA', 'ROW', 'ROW',
         'COUNT', 'ROW', 'NUMBER', 'ROWCOUNT', 'ROWGUIDCOL', 'ROWID', 'ROWNUM', 'ROWS', 'RULE', 'SAVE', 'SAVEPOINT', 'SCALE', 'SCHEMA', 'SCHEMA', 'NAME',
         'SCHEMAS', 'SCOPE', 'SCOPE', 'CATALOG', 'SCOPE', 'NAME', 'SCOPE', 'SCHEMA', 'SCROLL', 'SEARCH', 'SECOND', 'SECOND', 'MICROSECOND', 'SECTION',
         'SECURITY', 'SELECT', 'SELF', 'SENSITIVE', 'SEPARATOR', 'SEQUENCE', 'SERIALIZABLE', 'SERVER', 'NAME', 'SESSION', 'SESSION', 'USER', 'SET', 'SETOF',
         'SETS', 'SETUSER', 'SHARE', 'SHOW', 'SHUTDOWN', 'SIGNAL', 'SIMILAR', 'SIMPLE', 'SIZE', 'SMALLINT', 'SOME', 'SONAME', 'SOURCE', 'SPACE', 'SPATIAL',
         'SPECIFIC', 'SPECIFIC', 'NAME', 'SPECIFICTYPE', 'SQL', 'SQL', 'BIG', 'RESULT', 'SQL', 'BIG', 'SELECTS', 'SQL', 'BIG', 'TABLES', 'SQL', 'CALC',
         'FOUND', 'ROWS', 'SQL', 'LOG', 'OFF', 'SQL', 'LOG', 'UPDATE', 'SQL', 'LOW', 'PRIORITY', 'UPDATES', 'SQL', 'SELECT', 'LIMIT', 'SQL', 'SMALL',
         'RESULT', 'SQL', 'WARNINGS', 'SQLCA', 'SQLCODE', 'SQLERROR', 'SQLEXCEPTION', 'SQLSTATE', 'SQLWARNING', 'SQRT', 'SSL', 'STABLE', 'START', 'STARTING',
         'STATE', 'STATEMENT', 'STATIC', 'STATISTICS', 'STATUS', 'STDDEV', 'POP', 'STDDEV', 'SAMP', 'STDIN', 'STDOUT', 'STORAGE', 'STRAIGHT', 'JOIN',
         'STRICT', 'STRING', 'STRUCTURE', 'STYLE', 'SUBCLASS', 'ORIGIN', 'SUBLIST', 'SUBMULTISET', 'SUBSTRING', 'SUCCESSFUL', 'SUM', 'SUPERUSER', 'SYMMETRIC',
         'SYNONYM', 'SYSDATE', 'SYSID', 'SYSTEM', 'SYSTEM', 'USER', 'TABLE', 'TABLE', 'NAME', 'TABLES', 'TABLESAMPLE', 'TABLESPACE', 'TEMP', 'TEMPLATE',
         'TEMPORARY', 'TERMINATE', 'TERMINATED', 'TEXT', 'TEXTSIZE', 'THAN', 'THEN', 'TIES', 'TIME', 'TIMESTAMP', 'TIMEZONE', 'HOUR', 'TIMEZONE', 'MINUTE',
         'TINYBLOB', 'TINYINT', 'TINYTEXT', 'TO', 'TOAST', 'TOP', 'TOP', 'LEVEL', 'COUNT', 'TRAILING', 'TRAN', 'TRANSACTION', 'TRANSACTION', 'ACTIVE',
         'TRANSACTIONS', 'COMMITTED', 'TRANSACTIONS', 'ROLLED', 'BACK', 'TRANSFORM', 'TRANSFORMS', 'TRANSLATE', 'TRANSLATION', 'TREAT', 'TRIGGER', 'TRIGGER',
         'CATALOG', 'TRIGGER', 'NAME', 'TRIGGER', 'SCHEMA', 'TRIM', 'TRUE', 'TRUNCATE', 'TRUSTED', 'TSEQUAL', 'TYPE', 'UESCAPE', 'UID', 'UNBOUNDED',
         'UNCOMMITTED', 'UNDER', 'UNDO', 'UNENCRYPTED', 'UNION', 'UNIQUE', 'UNKNOWN', 'UNLISTEN', 'UNLOCK', 'UNNAMED', 'UNNEST', 'UNSIGNED', 'UNTIL',
         'UPDATE', 'UPDATETEXT', 'UPPER', 'USAGE', 'USE', 'USER', 'USER', 'DEFINED', 'TYPE', 'CATALOG', 'USER', 'DEFINED', 'TYPE', 'CODE', 'USER', 'DEFINED',
         'TYPE', 'NAME', 'USER', 'DEFINED', 'TYPE', 'SCHEMA', 'USING', 'UTC', 'DATE', 'UTC', 'TIME', 'UTC', 'TIMESTAMP', 'VACUUM', 'VALID', 'VALIDATE',
         'VALIDATOR', 'VALUE', 'VALUES', 'VAR', 'POP', 'VAR', 'SAMP', 'VARBINARY', 'VARCHAR', 'VARCHAR', 'VARCHARACTER', 'VARIABLE', 'VARIABLES', 'VARYING',
         'VERBOSE', 'VIEW', 'VOLATILE', 'WAITFOR', 'WHEN', 'WHENEVER', 'WHERE', 'WHILE', 'WIDTH', 'BUCKET', 'WINDOW', 'WITH', 'WITHIN', 'WITHOUT', 'WORK',
         'WRITE', 'WRITETEXT', 'X', 'XOR', 'YEAR', 'YEAR', 'MONTH', 'ZEROFILL', 'ZONE'];
        return ($.inArray(word,sys)!=-1);
    }
    //]]>
    </script>
</div>

<!-- resetpass -->
<div id="dev-u-tab-resetpass" class="dev-u-tab" style="display: none;">

   <table class="admtbl tbledit" style="margin:10px 0 10px 10px; border-collapse:separate;">
        <tr>
            <td class="row1">
                <p class="text-error">Выполнить обнуление паролей всех пользователей к 'test'?</p>
                <label class="checkbox"><input type="checkbox" name="salt" checked="checked" id="resetpass-use-salt" /> соль используется</label>
            </td>
        </tr>
        <tr class="footer">
            <td><input type="button" class="btn btn-danger button delete" value="Выполнить" onclick="devResetPass($(this));" /></td>
        </tr>
    </table>

    <script type="text/javascript">
        function devResetPass(btn)
        {
            if( ! bff.confirm('sure')) return;

            btn.val('Подождите...');
            var useSalt = ( $('#resetpass-use-salt').is(':checked') ? 1 : 0 );
            bff.ajax('<?= $this->adminLink(bff::$event.'&act=rstpass1') ?>', {salt: useSalt},
            function(data){
                if(data && data.success) {
                    bff.error(data.result, {success:true});
                }
                btn.val('Выполнить');
            });
        }
    </script>

</div>

<!-- install-sql -->
<div id="dev-u-tab-install-sql" class="dev-u-tab" style="display: none;">

   <table class="admtbl tbledit" style="margin:10px 0 10px 10px; border-collapse:separate;">
        <tr>
            <td class="row1">
                <p class="text-error">Выполнить сброс базы на основе файлов "install.sql"?</p>
            </td>
        </tr>
        <tr class="footer">
            <td>
                <input type="button" class="btn btn-danger button delete" value="Выполнить" onclick="devInstallSql($(this));" />
            </td>
        </tr>
    </table>

    <script type="text/javascript">
        function devInstallSql(btn)
        {
            if( ! bff.confirm('sure')) return;

            btn.val('Подождите...');
            bff.ajax('<?= $this->adminLink(bff::$event.'&act=install-sql') ?>', {},
            function(data){
                if(data && data.success) {
                    bff.success('Сброс базы был успешно выполнен');
                }
                btn.val('Выполнить');
            });
        }
    </script>

</div>

</form>
