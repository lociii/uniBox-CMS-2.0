function toggle_instanthelp(theme_base, text_show, text_vanish)
{
    isMSIE = (navigator.appName == "Microsoft Internet Explorer");
    element_help = document.getElementById("page_help");
    element_main = document.getElementById("page_main");
    element_main_inner = document.getElementById("page_main_inner");
    element_image = document.getElementById("image_toggle_instanthelp");
    element_tr = document.getElementById("main_corner_tr");
    element_br = document.getElementById("main_corner_br");
    if (element_image)
    {
        if (element_help.style.display == 'none')
        {
            element_help.style.display = 'block';
            element_main.style.marginRight = '17em';
            element_main_inner.style.marginRight = '8px';
            element_image.setAttribute('src', theme_base + '/media/images/instanthelp_vanish.gif');
            element_image.setAttribute('alt', text_vanish);
            element_image.setAttribute('title', text_vanish);
            if (isMSIE)
            {
                element_tr.style.right = '0px';
                element_br.style.right = '0px';
            }
        }
        else
        {
            element_help.style.display = 'none';
            element_main.style.marginRight = '0em';
            element_main_inner.style.marginRight = '0px';
            element_image.setAttribute('src', theme_base + '/media/images/instanthelp_show.gif');
            element_image.setAttribute('alt', text_show);
            element_image.setAttribute('title', text_show);
            if (isMSIE)
            {
                element_tr.style.right = '-1px';
                element_br.style.right = '-1px';
            }
        }
    }
    else
    {
        element_help.style.display = 'none';
        element_main.style.marginRight = '0em';
        element_main_inner.style.marginRight = '0px';
        if (isMSIE)
        {
            element_tr.style.right = '-1px';
            element_br.style.right = '-1px';
        }
    }
}