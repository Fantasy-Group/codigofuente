<?php 
/*
	File: 	stage_1.php 
	Version: 1.0

*/
function step_1(){ 
	if( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['agree'] ) ){
		header('Location: index.php?step=2');
		exit;
	}
	if( $_SERVER['REQUEST_METHOD'] == 'POST' && !isset( $_POST['agree'] ) ){
		echo '<div class="warning"><strong style="color:#A9444D;">Oh Snap!</strong> Did you forget to tick the "I agree" tick box below?</div>';
}
	session_start(); 
?>
	
	<form action="index.php?step=1" method="post">
		<div class="actions-bg-left">
		<div class="actions-bg-right">
		<div class="actions-bg-top">
		<div class="actions-bg-bottom">
		<div class="actions-top-left">
		<div class="actions-top-right">
		<div class="actions-bottom-left">
		<div class="actions-bottom-right">
		<div class="step_content" style="height:255px; overflow-y:scroll;">
		
			<h3>Welcome to the Magmi Installer Script</h3>
			<p>
				<br />This installer script was created to make the installation of Magmi as straight forwards as possible without a "Degree in Nerd" being required.
				<br /><br />
				Check over the licenses below, tick the box and press continue.
				<br /><br />
				<strong>PS.</strong> If you are a "Nerd" (<em>yay!</em>) &amp; can make this installer better, please do so! You can help <a href="http://understandinge.com/forum/all-things-coding/" target="_blank">here</a>.
				<br /><br />
			</p>
		
			<h3>LICENSE AGREEMENTS</h3>
			<p><br />This installer and Magmi are distributed under two different license agreements.
			<br /><br />
			<strong>Magmi License</strong>
			<br /><br />
			Magmi is disctrubuted under the <a href="http://opensource.org/licenses/MIT" target="_blank">The MIT License</a>.
			</p>
<pre>			
Copyright (C) 2012 by Dweeves (S.BRACQUEMONT)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
</pre>
			
			<p>
			<br /><br />
			<strong>Magmi Installation Script License</strong>
			<br /><br />
			This Magmi Installation Script distributed under the <a href="http://www.gnu.org/licenses/gpl-3.0.txt" target="_blank">GNU GENERAL PUBLIC LICENSE</a>.
			<br /><br />
			</p>
<pre>
Copyright (C) 2014  Matthew Ogborne

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see http://www.gnu.org/licenses/.
</pre>			
			
		</div>
		</div>
		</div>
		</div>
		</div>
		</div>
		</div>
		</div>
		</div>
		<div class="next-step">
			<p class="agree-license">I agree to the licenses
				<input type="checkbox" name="agree" class="agreement" />
			</p>
			<input type="submit" class="continue" value=""/>
		</div>
	</form>
<?php 
}