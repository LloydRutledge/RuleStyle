test:
	wget http://localhost/RuleStyle/FresnelRules.php?resource=http%3A%2F%2Fexample.org%2F%23Car_A      -O testResults/Car_A.html
	wget http://localhost/RuleStyle/FresnelRules.php?resource=http%3A%2F%2Fexample.org%2F%23inf        -O testResults/inf.html
	wget http://localhost/RuleStyle/FresnelRules.php?resource=http%3A%2F%2Fexample.org%2F%23Movement_X -O testResults/Movement_X.html
	diff testExpect/ testResults/
