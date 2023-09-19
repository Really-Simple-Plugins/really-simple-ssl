make = ({linebreak = '', indent = '', shortArraySyntax = false} = {}) ->
  arrOpen = if shortArraySyntax then '[' else 'array('
  arrClose = if shortArraySyntax then ']' else ')'
  nest = {
    '[object Array]': (obj, parentIndent) ->
      for value in obj
        transform(value, parentIndent)

    '[object Object]': (obj, parentIndent) ->
      for own key, value of obj
        transform(key, parentIndent) + ' => ' + transform(value, parentIndent)
  }

  transform = (obj, parentIndent = '') ->
    objType = Object.prototype.toString.call(obj)
    switch objType
      when '[object Null]', '[object Undefined]'
        result = 'null'
      when '[object String]'
        result = "'" + obj.replace(///\\///g, '\\\\').replace(///\'///g, "\\'") + "'"
      when '[object Number]', '[object Boolean]'
        result = obj.toString()
      when '[object Array]', '[object Object]'
        nestIndent = parentIndent + indent
        items = nest[objType](obj, nestIndent)
        result = """
          #{arrOpen}#{linebreak + nestIndent}#{
            items.join(',' + if linebreak == '' then ' ' else linebreak + nestIndent)
          }#{linebreak + parentIndent}#{arrClose}
        """
      else
        result = 'null'
    result

json2php = make()

json2php.make = make

if typeof module isnt 'undefined' and module.exports
  module.exports = json2php
  # Not that good but useful
  global.json2php = json2php
