import * as React from "react";
const SvgMnMongolia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="MN_-_Mongolia_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <rect width={16} height={12} fill="#fff" rx={0} />
    </mask>
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#MN_-_Mongolia_svg__a)">
      <path fill="#4C67E8" d="M5 0h6v12H5V0Z" />
      <path fill="#C51918" d="M11 0h5v12h-5V0ZM0 0h6v12H0V0Z" />
      <path
        fill="#F8D000"
        d="M3.007 3.442c-.507 0-.44-.494-.44-.494l.24-.518v.519c0 .066.091-.067.091-.228 0-.16.109-.401.109-.401l.007-.16a.47.47 0 0 0 .106.13l.03.03c.062.064.053.234.046.38-.007.134-.012.248.037.248.102 0 .095-.459.095-.459l.172.46s.014.493-.493.493Zm0-1.41c.013-.075.011.026.007.128a.218.218 0 0 1-.007-.128Zm.762 2.31a.739.739 0 0 1-.75.727.739.739 0 0 1-.75-.727c0-.401.336-.727.75-.727s.75.326.75.727Zm-1.884 1.36H1v4.286h.885V5.702Zm3.115 0h-.885v4.286H5V5.702Zm-2.885.037.863.534.972-.534H2.115ZM2.978 10l-.863-.534H3.95L2.978 10Zm-.863-3.553h1.808v.336H2.115v-.336Zm1.808 2.497H2.115v.336h1.808v-.336Zm-.885-.186c.51 0 .924-.4.924-.894s-.414-.895-.924-.895-.923.4-.923.895c0 .494.414.894.923.894Zm-1.036-4.15s.03.934.95.934c.921 0 1.07-.934 1.07-.934s-.356.572-1.01.572-1.01-.572-1.01-.572Z"
      />
    </g>
  </svg>
);
export default SvgMnMongolia;
