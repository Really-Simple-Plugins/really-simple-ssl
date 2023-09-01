import * as React from "react";
const SvgTkTokelau = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="TK_-_Tokelau_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#TK_-_Tokelau_svg__a)">
      <path
        fill="#2E42A5"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="TK_-_Tokelau_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g fillRule="evenodd" clipRule="evenodd" mask="url(#TK_-_Tokelau_svg__b)">
        <path
          fill="#F7FCFF"
          d="m1.647 5.032-.443.274.172-.46L1 4.498h.456L1.647 4l.146.498h.457l-.33.348.162.46-.435-.274ZM3.647 3.032l-.443.274.172-.46L3 2.498h.457L3.647 2l.146.498h.457l-.33.348.162.46-.435-.274ZM5.647 5.032l-.443.274.172-.46L5 4.498h.457L5.647 4l.146.498h.457l-.33.348.162.46-.435-.274ZM3.647 7.032l-.443.274.172-.46L3 6.498h.457L3.647 6l.146.498h.457l-.33.348.162.46-.435-.274Z"
        />
        <path
          fill="#FECA00"
          d="M12.421 2.732c-2.042 1.008-8.75 5.54-8.75 5.54h11.175c-.111-.024-.216-.045-.314-.065-.82-.163-1.225-.244-2.111-2.008-.992-1.976 0-3.467 0-3.467Zm-9.113 6.25-.153.38.153.418 11.377.22.315-.58-.315-.417-11.377-.02Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgTkTokelau;
