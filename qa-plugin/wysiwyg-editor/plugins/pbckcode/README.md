# PBCKCode

A CKEditor plugin to easily add code into your articles.
The plugin will create a dialog where you will be able to format your code as your will. When you press the **OK** button, the plugin will create a *pre* tag with your code inside.

# Demo
See it in action ! http://prbaron.github.com/PBCKCode/

# Installation
 - Download the plugin from the Github repository : [https://github.com/prbaron/PBCKCode/tags](https://github.com/prbaron/PBCKCode/tags)
 - Place the folder into the plugins folder of CKEditor ( *{Path to CKEDitor}/plugins/* )
 - Open the config.js file and add the following lines :
<pre>
// I juste paste the important lines, you can add all the toolbar buttons you want
config.toolbarGroups = [
	{ name: 'others' },
];
config.extraPlugins = 'pbckcode';
</pre>

And you are good to go! You will have the same configuration than the demo.

#Configuration
This plugin comes with a full set of themes and modes, you can see all these things in the Ace website : [http://ace.ajax.org/](http://ace.ajax.org/).

Here is properties you can customize and their default value :
<pre>
config.pbckcode  = {
	'cls'         : 'prettyprint linenums',	// the class(es) added to the pre tag, useful if you use a syntax highlighter (here it is Google Prettify)
	'modes'       : [
		['PHP'  , 'php'],
		['HTML' , 'html'],
		['CSS'  , 'css'] ], // all the languages you want to deal with in the plugin
  	'defaultMode' : 'php', // the default value for the mode select. Well in fact it is the first value of the mode array
	'theme' : 'textmate' // the theme of the code editor
};
</pre>

## Mode
The mode property is an associative array of all the mode you want to be able to choose in the select. Each language has to be initialized in an array containing the label and the value. The defaut configuration create this select :
<pre>
&lt;select&gt;
	&lt;option value="php" selected&gt;PHP&lt;/option&gt;
	&lt;option value="html"&gt;HTML&lt;/option&gt;
	&lt;option value="css"&gt;CSS&lt;/option&gt;
</select>
</pre>

Here is an array with all the modes you can use in the plugin :
<pre>
'modes' : [
	["C/C++"        , "c_pp"],
	["C9Search"     , "c9search"],
	["Clojure"      , "clojure"],
	["CoffeeScript" , "coffee"],
	["ColdFusion"   , "coldfusion"],
	["C#"           , "csharp"],
	["CSS"          , "css"],
	["Diff"         , "diff"],
	["Glsl"         , "glsl"],
	["Go"           , "golang"],
	["Groovy"       , "groovy"],
	["haXe"         , "haxe"],
	["HTML"         , "html"],
	["Jade"         , "jade"],
	["Java"         , "java"],
	["JavaScript"   , "javascript"],
	["JSON"         , "json"],
	["JSP"          , "jsp"],
	["JSX"          , "jsx"],
	["LaTeX"        , "latex"],
	["LESS"         , "less"],
	["Liquid"       , "liquid"],
	["Lua"          , "lua"],
	["LuaPage"      , "luapage"],
	["Markdown"     , "markdown"],
	["OCaml"        , "ocaml"],
	["Perl"         , "perl"],
	["pgSQL"        , "pgsql"],
	["PHP"          , "php"],
	["Powershell"   , "powershel1"],
	["Python"       , "python"],
	["R"            , "ruby"],
	["OpenSCAD"     , "scad"],
	["Scala"        , "scala"],
	["SCSS/Sass"    , "scss"],
	["SH"           , "sh"],
	["SQL"          , "sql"],
	["SVG"          , "svg"],
	["Tcl"          , "tcl"],
	["Text"         , "text"],
	["Textile"      , "textile"],
	["XML"          , "xml"],
	["XQuery"       , "xq"],
	["YAML"         , "yaml"]
];
</pre>

## Theme
If you want to change the theme, just pick one of these themes :
**Bright** : "chrome", "clouds", "crimson_editor", "dawn", "dreamweaver", "eclipse", "github", "solarized_light", "textmate", "tomorrow".

**Dark** : "clouds_midnight", "cobalt", "idle_fingers", "kr_theme", "merbivore", "merbivore_soft", "mono_industrial", "monokai", "pastel_on_dark", "solarized_dark",  "tomorrow_night", "tomorrow_night_blue", "tomorrow_night_bright", "tomorrow_night_eighties", "twilight", "vibrant_ink".


# Special Thanks
CKEditor : [http://ckeditor.com/](http://ckeditor.com/)
ACE : [http://ace.ajax.org/](http://ace.ajax.org/)

# Credits
#### Pierre Baron
Website : [http://www.pierrebaron.fr](http://www.pierrebaron.fr)
Twitter : [@prbaron](https://twitter.com/prbaron)
Contact : <prbaron22@gmail.com>
