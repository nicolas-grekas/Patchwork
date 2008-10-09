/* Tokenizer for JavaScript code */

var tokenizePHP = (function() {
  // Advance the stream until the given character (not preceded by a
  // backslash) is encountered, or the end of the line is reached.
  function nextUntilUnescaped(source, end, result){
    var escaped = false;
    var next;
    while(!source.endOfLine()){
      var next = source.next();
      if (next == end && !escaped)
        break;
      escaped = next == "\\";
    }
    return result;
  }

  // A map of JavaScript's keywords. The a/b/c keyword distinction is
  // very rough, but it gives the parser enough information to parse
  // correct code correctly (we don't care that much how we parse
  // incorrect code). The style information included in these objects
  // is used by the highlighter to pick the correct CSS style for a
  // token.
  var keywords = function(){
    function result(type, style){
      return {type: type, style: style};
    }
    // keywords that take a parenthised expression, and then a
    // statement (if)
    var keywordA = result("keyword a", "js-keyword");
    // keywords that take just a statement (else)
    var keywordB = result("keyword b", "js-keyword");
    // keywords that optionally take an expression, and form a
    // statement (return)
    var keywordC = result("keyword c", "js-keyword");
    var operator = result("operator", "js-keyword");
    var atom = result("atom", "js-atom");
    return {

      "if": keywordA, "switch": keywordA, "while": keywordA, "with": keywordA, "for": keywordA, "foreach": keywordA, "try": keywordA,
      "else": keywordB, "elseif": keywordB, "do": keywordB, "try": keywordB, "finally": keywordB, "exit": keywordB, "die": keywordB, "catch": keywordB,
	"declare": keywordB, "echo": keywordB, "eval": keywordB, "empty": keywordB,
      "return": keywordC, "break": keywordC, "continue": keywordC, "new": keywordC, "delete": keywordC, "throw": keywordC,
	"array": keywordC, "include": keywordC, "include_once": keywordC, "require": keywordC, "require_once": keywordC, "print": keywordC, "isset": keywordC, "unset": keywordC, "list": keywordC,

      "in": operator, "and": operator, "or": operator, "xor": operator,
      "function": result("function", "js-keyword"), "catch": result("catch", "js-keyword"), "var": result("var", "js-keyword"),
      "case": result("case", "js-keyword"), "default": result("default", "js-keyword"),
      "true": atom, "false": atom, "null": atom, "undefined": atom,

      "__FILE__": result("__FILE__", "js-string"), "__CLASS__": result("__CLASS__", "js-string"), "__LINE__": result("__LINE__", "js-string"), "__FUNCTION__": result("__FUNCTION__", "js-string"), "__METHOD__": result("__METHOD__", "js-string"),
	"exception": result("exception", "js-keyword"), "php_user_filter": result("php_user_filter", "js-keyword"),
	"class": result("class", "js-keyword"), "new": result("new", "js-keyword"), "extends": result("extends", "js-keyword"), "final": result("final", "js-keyword"), "interface": result("interface", "js-keyword"), "implements": result("final", "js-keyword"), "abstract": result("abstract", "js-keyword"),
	"as": result("as", "js-keyword"), "static": result("static", "js-keyword"), "public": result("public", "js-keyword"), "private": result("private", "js-keyword"), "protected": result("protected", "js-keyword"),
	"global": result("global", "js-keyword"), "clone": result("clone", "js-keyword")

    };
  }();

  // Some helper regexp matchers.
  var isOperatorChar = matcher(/[\+\-\*\&\%\/=<>!\?]/);
  var isDigit = matcher(/[0-9]/);
  var isHexDigit = matcher(/[0-9A-Fa-f]/);
  var isWordChar = matcher(/[\w\$_]/);

  // Wrapper around jsToken that helps maintain parser state (whether
  // we are inside of a multi-line comment and whether the next token
  // could be a regular expression).
  function phpTokenState(inComment, regexp) {
    return function(source, setState) {
      var newInComment = inComment;
      var type = phpToken(inComment, regexp, source, function(c) {newInComment = c;});
      var newRegexp = type.type == "operator" || type.type == "keyword c" || type.type.match(/^[\[{}\(,;:]$/);
      if (newRegexp != regexp || newInComment != inComment)
        setState(phpTokenState(newInComment, newRegexp));
      return type;
    };
  }

  // The token reader, inteded to be used by the tokenizer from
  // tokenize.js (through jsTokenState). Advances the source stream
  // over a token, and returns an object containing the type and style
  // of that token.
  function phpToken(inComment, regexp, source, setComment) {
    function readHexNumber(){
      source.next(); // skip the 'x'
      source.nextWhile(isHexDigit);
      return {type: "number", style: "js-atom"};
    }

    function readNumber() {
      source.nextWhile(isDigit);
      if (source.equals(".")){
        source.next();
        source.nextWhile(isDigit);
      }
      if (source.equals("e") || source.equals("E")){
        source.next();
        if (source.equals("-"))
          source.next();
        source.nextWhile(isDigit);
      }
      return {type: "number", style: "js-atom"};
    }
    // Read a word, look it up in keywords. If not found, it is a
    // variable, otherwise it is a keyword of the type found.
    function readWord() {
      source.nextWhile(isWordChar);
      var word = source.get();
      var known = keywords.hasOwnProperty(word) && keywords.propertyIsEnumerable(word) && keywords[word];
      return known ? {type: known.type, style: known.style, content: word} :
      {type: "variable", style: "js-variable", content: word};
    }
    function readRegexp() {
      nextUntilUnescaped(source, "/");
      source.nextWhile(matcher(/[gi]/));
      return {type: "regexp", style: "js-string"};
    }
    // Mutli-line comments are tricky. We want to return the newlines
    // embedded in them as regular newline tokens, and then continue
    // returning a comment token for every line of the comment. So
    // some state has to be saved (inComment) to indicate whether we
    // are inside a /* */ sequence.
    function readMultilineComment(start){
      var newInComment = true;
      var maybeEnd = (start == "*");
      while (true) {
        if (source.endOfLine())
          break;
        var next = source.next();
        if (next == "/" && maybeEnd){
          newInComment = false;
          break;
        }
        maybeEnd = (next == "*");
      }
      setComment(newInComment);
      return {type: "comment", style: "js-comment"};
    }
    function readOperator() {
      source.nextWhile(isOperatorChar);
      return {type: "operator", style: "js-operator"};
    }

    // Fetch the next token. Dispatches on first character in the
    // stream, or first two characters when the first is a slash.
    var ch = source.next();
    if (inComment)
      return readMultilineComment(ch);
    else if (ch == "\"" || ch == "'")
      return nextUntilUnescaped(source, ch, {type: "string", style: "js-string"});
    // with punctuation, the type of the token is the symbol itself
    else if (/[\[\]{}\(\),;\:\.]/.test(ch))
      return {type: ch, style: "js-punctuation"};
    else if (ch == "0" && (source.equals("x") || source.equals("X")))
      return readHexNumber();
    else if (isDigit(ch))
      return readNumber();
    else if (ch == "/"){
      if (source.equals("*"))
      { source.next(); return readMultilineComment(ch); }
      else if (source.equals("/"))
        return nextUntilUnescaped(source, null, {type: "comment", style: "js-comment"});
      else if (regexp)
        return readRegexp();
      else
        return readOperator();
    }
    else if (isOperatorChar(ch))
      return readOperator();
    else
      return readWord();
  }

  // The external interface to the tokenizer.
  return function(source, startState) {
    return tokenizer(source, startState || phpTokenState(false, true));
  };
})();
