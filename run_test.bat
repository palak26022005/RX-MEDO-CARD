@echo off
echo Running PHP test...
cd /d c:\xammp\htdocs\rxmedoo
c:\xammp\php\php.exe simple_test.php > test_output.txt 2>&1
echo Test completed. Check test_output.txt for results.
type test_output.txt
pause
