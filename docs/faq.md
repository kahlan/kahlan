## FAQ

**Q:** After an update my code doesn't work?<br>
**A:** Please, make sure that you cleared-up the **/tmp/kahlan** folder, and the updated version is a BC-compatible version.

**Q:** How can I use my **favorite framework** with Kahlan?<br>
**A:** You can look up in [Framework integration](integration.md).

**Q:** What if there is no info about my framework in integration?<br>
**A:** All frameworks which use a PSR-0 compatible loader can be integrated using the generic way. Otherwise you'll need to make a PR.

**Q:** Why do we need test doubles?<br>
**A:** Test doubles are mostly used for unit testing to isolate a behavior by bypassing a large part of the code which is not directly related to the subject of the test.

**Q:** When test doubles can ben used?<br>
**A:** Test doubles can be used to bypass database persistance part, bail out emailing processing, etc.

**Q:** What difference between stub and mock?<br>
**A:** There's so many different definitions around that's it's hard to tell which one makes more sense. [Test Double is a generic term for any case where you replace a production object for testing purposes](http://www.martinfowler.com/bliki/TestDouble.html) so stub, mock, fake, etc. are all subset of Test Double. And since in Kahlan instance doubles as well as normal instances have mock capabilities under the hood it doesn't make sense to make any distinction here.

**Q:** Where can i view current roadmap?<br>
**A:** You can view it [here](https://github.com/kahlan/kahlan/wiki/Roadmap)

**Q:** I have a question and can't solve it by myself!<br>
**A:** If this question is related to **Kahlan**, please open issue. Or ask your question in live on [IRC](index.md).
