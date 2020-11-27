module.exports = function(grunt) {
  var path = require('path');
  var _     = require('lodash');
  var global_config = {
    // path to task.js files, defaults to grunt dir
      configPath: path.join(process.cwd(), 'grunt-tasks-config/'),
      // auto grunt.initConfig
      init: true,
      // data passed into config ( => the basic grunt.initConfig(config) ). Can be used afterwards with < %= test % >
      data: {
        pkg: grunt.file.readJSON( 'package.json' ),
        paths : {
        	back_assets : 'back/assets/',
        	lang : 'lang/'
        },
        vars : {
        	textdomain: 'childify-me'
        },
       	tasks : {
       		'build' : [ 'replace', 'uglify', 'addtextdomain', 'makepot', 'potomo', 'cssmin' ]
       	},
       	uglify_requested_paths : {
       	  src : '' || grunt.option('src'),
       	  dest : '' || grunt.option('dest')
       	}
      }//data
  };//config

  // LOAD GRUNT PACKAGES AND CONFIGS
  // https://www.npmjs.org/package/load-grunt-config
  require( 'load-grunt-config' )( grunt , global_config );

  //http://www.thomasboyt.com/2013/09/01/maintainable-grunt.html
  //http://gruntjs.com/api/grunt.task#grunt.task.loadtasks
  //grunt.loadTasks('grunt-tasks');
  // REGISTER TASKS
  _.map( grunt.config('tasks'), function(task, name) {
  	grunt.registerTask(name, task);
  });

  //DEV WATCH EVENT
  //watch is enabled only in dev mode
  grunt.event.on('watch', function(action, filepath, target) {
    var files = [
      {
        expand: true,
        cwd: '.',
        src: [
        	filepath,
        ]
      }
    ];
    grunt.log.writeln( 'WATCH EVENT INFOS : ', grunt.task.current.name , action, filepath, target);
  });
};
