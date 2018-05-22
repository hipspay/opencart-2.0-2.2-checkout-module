<p align="center"><img src="https://hips.com/logo.svg"></p>

# Opencart 2.0 - 2.2 setup instructions

1. You need VQmod to use this extension. If you don't have this installed please go [here](https://github.com/vqmod/vqmod/releases).
2. Download our latest Opencart 2.0 - 2.2 Checkout module [here](https://github.com/hipspay/opencart-2.0-2.2-checkout-module/releases).
3. Unzip the downloaded [HIPS.OC.2.zip](https://github.com/hipspay/opencart-2.0-2.2-checkout-module/releases) file into a new folder.
4. Upload all the files in /Upload folder to your OpenCart root directory using a FTP client.
5. Login to your OpenCart admin panel and go to Extensions > Payments, find Hips Checkout, and click the [Install] link. Then the screen flickers and [Edit] link shows up. 
6. Click the [Edit] link.
7. Enter your **Public API Key** (will be found <a href="https://dashboard.hips.com/sales_channels" target="_blank">here</a>).
8. Enter your **Private API Key** (will be found <a href="https://dashboard.hips.com/sales_channels" target="_blank">here</a>).
9. Extended Cart: If Yes, then Hips checkout sidebar will display at checkout page. If No, then Opencart sidebar will display at checkout page.
10. Payment Type: Card Payment and Full Payment
D1. Full Payment: In case of full payment the checkout works with Inline iFrame which replace current checkout page.
D2. Card Payment: In case of card payment the checkout works with Outline credit card payment within current checkout(Opencart).
11. Save your settings.
12. Configure your shipping methods (will be found <a href="https://dashboard.hips.com/shippings" target="_blank">here</a>).
13. ==All done!==
14. (**optional**) If you want to accept Paypal, Invoice etc you may do that by <a href="https://dashboard.hips.com/account/relay" target="_blank">connecting those to your HIPS account</a>


## Contributing

If you want to contribute to a Hips project and make it better, your help is very welcome. Contributing is also a great way to learn more about social coding on Github, new technologies and and their ecosystems and how to make constructive, helpful bug reports, feature requests and the noblest of all contributions: a good, clean pull request.

### How to make a clean pull request

- Create a personal fork of the project on Github.
- Clone the fork on your local machine. Your remote repo on Github is called `origin`.
- Add the original repository as a remote called `upstream`.
- If you created your fork a while ago be sure to pull upstream changes into your local repository.
- Create a new branch to work on! Branch from `develop` if it exists, else from `master`.
- Implement/fix your feature, comment your code.
- Follow the code style of the project, including indentation.
- If the project has tests run them!
- Write or adapt tests as needed.
- Add or change the documentation as needed.
- Squash your commits into a single commit with git's [interactive rebase](https://help.github.com/articles/interactive-rebase). Create a new branch if necessary.
- Push your branch to your fork on Github, the remote `origin`.
- From your fork open a pull request in the correct branch. Target the project's `develop` branch if there is one, else go for `master`!
- ...
- Once the pull request is approved and merged you can pull the changes from `upstream` to your local repo and delete
your extra branch(es).

And last but not least: Always write your commit messages in the present tense. Your commit message should describe what the commit, when applied, does to the code – not what you did to the code.


