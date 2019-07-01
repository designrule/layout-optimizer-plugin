import $ from 'jquery';
import Auth from 'j-toker';

$(async () => {
  $('.signout').on('click', async() => {
    $("#my-submenu-form").submit();
  });
  
  $('.signin').on('click', async() => {
    try {
      await $.auth.configure({
        apiUrl: 'http://localhost:3000',
        storage: 'localStorage',
        authProviderPaths: {
          google: '/auth/google'
        }
      });
    }catch(e){
      console.log(e);
    }
    try {
      if(!$.auth.user.signedIn) {
        const user = await $.auth.oAuthSignIn({provider: 'google'});
      }
      const cred = $.auth.retrieveData('authHeaders');
      $('input[name="uid"]').val(cred.uid);
      $('input[name="email"]').val($.auth.user.email);
      $('input[name="client"]').val(cred.client);
      $('input[name="expiry"]').val(cred.expiry);
      $('input[name=access_token]').val(cred['access-token']);
      
      localStorage.removeItem('authHeaders');
      $("#my-submenu-form").submit();
      
      // const result = await $.ajax({
      //    url: `http://localhost:3000/api/v1/themes/155146272`,
      //    type: 'GET'
      // });
      // console.log(result);
      // console.log(result["theme"]);
    } catch(resp) {
      console.log(resp);
    }
  });
});

