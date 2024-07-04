document.addEventListener('DOMContentLoaded', () => {
  const htmlClassList = document.getElementsByTagName('html')[0].classList;
  htmlClassList.remove('no-js');
  htmlClassList.add('js');
});